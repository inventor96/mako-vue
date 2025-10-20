<?php
namespace app\http\controllers;

use mako\file\FileInfo;
use mako\file\FileSystem;
use mako\http\exceptions\NotFoundException;
use mako\http\routing\Controller;

class Fonts extends Controller {
	public function fonts(string $font, FileSystem $fs)
	{
		$path = __DIR__ . '/../../../node_modules/bootstrap-icons/font/fonts/' . $font;

		// check if the font exists
		if (!$fs->has($path))
		{
			throw new NotFoundException('The requested file does not exist.');
		}

		// set response headers
		$info = new FileInfo($path);
		$this->response->setType($info->getMimeType());
		$this->response->setCharset($info->getMimeEncoding());
		$this->response->headers->add('Content-Length', (string) $info->getSize(), true);

		// send the file
		$file = $fs->file($path);
		$file->rewind();
		$file->fpassthru();
		return null;
	}
}