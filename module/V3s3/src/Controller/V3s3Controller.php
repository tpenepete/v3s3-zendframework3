<?php

namespace V3s3\Controller;

use finfo;

use Zend\Mvc\Controller\AbstractRestfulController as ZF3_AbstractRestfulController;
use Zend\I18n\Translator\Translator as ZF3_Translator;
use Zend\View\Model\JsonModel as ZF3_JsonModel;
use Zend\Json\Json as ZF3_Json;
use Zend\Json\Exception\RuntimeException as ZF3_JsonRuntimeException;
use Zend\Http\PhpEnvironment\RemoteAddress as ZF3_RemoteAddress;

use V3s3\Model\V3s3Table;

use V3s3\Helper\V3s3Html;
use V3s3\Helper\V3s3Xml;

use V3s3\Exception\V3s3Exception;

class V3s3Controller extends ZF3_AbstractRestfulController
{
	protected $table;
	protected $translator;

	public function __construct(V3s3Table $table, ZF3_Translator $translator) {
		$this->table = $table;
		$this->translator = $translator;
	}

	// put
	public function update($name, $data) {
		$response = $this->getResponse();
		$ip = new ZF3_RemoteAddress;

		try {
			if (empty($name) || ($name == '/')) {
				throw new V3s3Exception($this->translator->translate('V3S3_EXCEPTION_PUT_EMPTY_OBJECT_NAME'), V3s3Exception::PUT_EMPTY_OBJECT_NAME);
			} else if (strlen($name) > 1024) {
				throw new V3s3Exception($this->translator->translate('V3S3_EXCEPTION_OBJECT_NAME_TOO_LONG'), V3s3Exception::OBJECT_NAME_TOO_LONG);
			}
		} catch(V3s3Exception $e) {
			return new ZF3_JsonModel(
				[
					'status'=>0,
					'code'=>$e->getCode(),
					'message'=>$e->getMessage()
				]
			);
		}

		$row = $this->table->put(
			[
				'ip'=>$ip->getIpAddress(),
				'name'=>$name,
				'data'=>$data,
				'mime_type'=>(new finfo(FILEINFO_MIME))->buffer($data),
			]
		);

		$response->getHeaders()->addHeaderLine('v3s3-object-id', $row['id']);

		return new ZF3_JsonModel([
			'status'=>1,
			'message'=>$this->translator->translate('V3S3_MESSAGE_PUT_OBJECT_ADDED_SUCCESSFULLY'),
		]);
	}

	// get
	public function get($name) {
		$request = $this->getRequest();
		$response = $this->getResponse();

		try {
			if (strlen($name) > 1024) {
				throw new V3s3Exception($this->translator->translate('V3S3_EXCEPTION_OBJECT_NAME_TOO_LONG'), V3s3Exception::OBJECT_NAME_TOO_LONG);
			}
		} catch(V3s3Exception $e) {
			return new ZF3_JsonModel(
				[
					'status'=>0,
					'code'=>$e->getCode(),
					'message'=>$e->getMessage()
				]
			);
		}

		$row = $this->table->get(
			array_replace(
				$request->getQuery()->toArray(),
				[
					'name'=>$name,
				]
			)
		);

		if(!empty($row['status'])) {
			$response->setContent($row['data']);

			if(empty($row['mime_type'])) {
				$row['mime_type'] = (new finfo(FILEINFO_MIME))->buffer($row['data']);
			}
			$response->getHeaders()->addHeaders(
				[
					'v3s3-object-id'=>$row['id'],
					'Content-Type'=>$row['mime_type'],
					'Content-Length'=>strlen($row['data'])
				]
			);
			if(!empty($request->getQuery('download'))) {
				$filename = basename($name);
				$response->addHeaderLine('Content-Disposition', 'attachment; filename="'.$filename.'"');
			}

			return $response;
		} else {
			$response->setStatusCode(404);

			return new ZF3_JsonModel(
				[
					'status'=>1,
					'results'=>0,
					'message'=>$this->translator->translate('V3S3_MESSAGE_404')
				]
			);
		}
	}

	// delete
	public function delete($name) {
		$request = $this->getRequest();
		$response = $this->getResponse();
		$ip = new ZF3_RemoteAddress;
		
		try {
			if (empty($name) || ($name == '/')) {
				throw new V3s3Exception($this->translator->translate('V3S3_EXCEPTION_DELETE_EMPTY_OBJECT_NAME'), V3s3Exception::DELETE_EMPTY_OBJECT_NAME);
			} else if (strlen($name) > 1024) {
				throw new V3s3Exception($this->translator->translate('V3S3_EXCEPTION_OBJECT_NAME_TOO_LONG'), V3s3Exception::OBJECT_NAME_TOO_LONG);
			}
		} catch(V3s3Exception $e) {
			return new ZF3_JsonModel(
				[
					'status'=>0,
					'code'=>$e->getCode(),
					'message'=>$e->getMessage()
				]
			);
		}

		$input = $request->getQuery()->toArray();
		$row = $this->table->api_delete(
			array_replace(
				$input,
				[
					'name'=>$name,
					'ip_deleted_from'=>$ip->getIpAddress()
				]
			)
		);

		if(empty($row)) {
			$response->setStatusCode(404);

			return new ZF3_JsonModel(
				[
					'status'=>1,
					'results'=>0,
					'message'=>$this->translator->translate('V3S3_MESSAGE_NO_MATCHING_RESOURCES')
				]
			);
		} else {
			$response->getHeaders()->addHeaderLine('v3s3-object-id', $row['id']);

			return new ZF3_JsonModel(
				[
					'status'=>1,
					'results'=>1,
					'message'=>$this->translator->translate('V3S3_MESSAGE_DELETE_OBJECT_DELETED_SUCCESSFULLY')
				]
			);
		}
	}

	// post
	public function create($data) {
		$request = $this->getRequest();
		$response = $this->getResponse();

		$name = $this->getEvent()->getRouteMatch()->getParam('id');

		$input = $request->getContent();

		try {
			$parsed_input = (!empty($input)?ZF3_Json::decode($input, ZF3_Json::TYPE_ARRAY):[]);
		} catch(ZF3_JsonRuntimeException $e) {
			return new ZF3_JsonModel(
				[
					'status'=>0,
					'code'=>$e->getCode(),
					'message'=>$e->getMessage()
				]
			);
		}

		if(!empty($input) && empty($parsed_input)) {
			try {
				throw new V3s3Exception($this->translator->translate('V3S3_EXCEPTION_POST_INVALID_REQUEST'), V3s3Exception::POST_INVALID_REQUEST);
			} catch(V3s3Exception $e) {
				return new ZF3_JsonModel(
					[
						'status'=>0,
						'code'=>$e->getCode(),
						'message'=>$e->getMessage()
					]
				);
			}
		}

		$attr = (!empty($parsed_input['filter'])?$parsed_input['filter']:[]);
		if(!empty($name) && ($name != '/')) {
			$attr['name'] = $name;
		}

		$rows = $this->table->post(
			$attr
		);

		if(!empty($rows)) {
			foreach ($rows as &$_row) {
				unset($_row['id']);
				unset($_row['timestamp']);
				unset($_row['hash_name']);
				unset($_row['timestamp_deleted']);
				if(empty($_row['mime_type'])) {
					$_row['mime_type'] = (new finfo(FILEINFO_MIME))->buffer($_row['data']).' (determined using PHP finfo)';
				}
				$_row['data'] = (new finfo(FILEINFO_MIME))->buffer($_row['data']);
			}

			$format = ((!empty($parsed_input['format'])&&in_array($parsed_input['format'], ['json', 'xml', 'html']))?strtolower($parsed_input['format']):'json');
			switch($format) {
				case 'xml':
					$rows = V3s3Xml::simple_xml($rows);
					$response->getHeaders()->addHeaderLine('Content-Type', 'text/xml; charset=utf-8');
					$response->setContent($rows);
					return $response;
					break;
				case 'html':
					$rows = V3s3Html::simple_table($rows);
					$response->getHeaders()->addHeaderLine('Content-Type', 'text/html; charset=utf-8');
					$response->setContent($rows);
					return $response;
					break;
				case 'json':
				default:
					return new ZF3_JsonModel($rows, ['prettyPrint'=>true]);
					break;
			}
		} else {
			return new ZF3_JsonModel(
				[
					'status'=>1,
					'results'=>0,
					'message'=>$this->translator->translate('V3S3_MESSAGE_NO_MATCHING_RESOURCES')
				]
			);
		}
	}
}