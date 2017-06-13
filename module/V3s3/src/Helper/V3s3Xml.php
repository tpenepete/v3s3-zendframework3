<?php
namespace V3s3\Helper;

use DOMDocument;

class V3s3Xml {
	static function simple_xml($rows) {
		$xml = new DOMDocument('1.0', 'utf-8');
		$xml->preserveWhiteSpace = false;
		$xml->formatOutput = true;
		$root = $xml->createElement('top');
		$xml->appendChild($root);
		foreach ($rows as $index => $row) {
			$node = $xml->createelement('item' . $index);
			foreach ($row as $k => $v) {
				$property = $xml->createelement($k);
				$text = $xml->createTextNode($v);
				$property->appendChild($text);
				$node->appendChild($property);
			}
			$root->appendChild($node);
		}

		return $xml->saveXml();
	}
}