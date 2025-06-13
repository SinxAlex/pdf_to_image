<?php

namespace PdfToImage;

use Imagick;
use PdfToImage\MysqlDatabase;

class PdfToImage
{
    const DIR_PDF_FILES = __DIR__ . "/pdf";
    const DIR_IMG_FILES = __DIR__ . "/img";
    const MIN_SIZE = 0;
    const MAX_SIZE = 2097152;

    public $hight;
    public $width;
    public $checksumFiles = [];
    public $format;
    public $files = [];
    public $size;

    public function __construct()
    {
       // $db = new MySQLDatabase("localhost", "your_username", "your_password", "your_database");
        $this->files = array_diff(scandir(self::DIR_PDF_FILES), ['.', '..']);
        $this->checkSum();

    }

    public function checkSum()
    {
        foreach ($this->files as $file) {
            $filePath = self::DIR_PDF_FILES . "/" . $file;
            $this->checksumFiles[$file] = hash_file('sha256', $filePath);
        }

        return $this->checksumFiles;
    }


    public function convertImg()
    {
        foreach ($this->files as $file)
        {
          $imagick=new Imagick();
            $imagick->setResolution(300, 300); // Увеличьте DPI для лучшего качества
            $imagick->readImage(self::DIR_PDF_FILES."/".$file);
            $file=explode('.',$file)[0];
                $imagick->resetIterator();
                $combined = $imagick->appendImages(true);
                $combined->setImageFormat('jpg');
                $combined->writeImage(self::DIR_IMG_FILES."/".$file.".jpg");
                $imageInfo = getimagesize(self::DIR_IMG_FILES."/".$file.".jpg");
                self::resizeImage(self::DIR_IMG_FILES."/".$file.".jpg",self::DIR_IMG_FILES."/".$file.".jpg",$maxHeight = 5000);
        }

    }

    static function resizeImage($sourcePath, $targetPath, $maxHeight = 5000)
    {
        // Получаем информацию об изображении
        list($originalWidth, $originalHeight, $type) = getimagesize($sourcePath);

        // Если высота меньше или равна максимальной - просто копируем файл
        if ($originalHeight <= $maxHeight) {
            if ($sourcePath !== $targetPath) {
                copy($sourcePath, $targetPath);
            }
            return true;
        }

        // Вычисляем новые размеры пропорционально
        $ratio = $maxHeight / $originalHeight;
        $newWidth = round($originalWidth * $ratio);
        $newHeight = $maxHeight;

        // Создаем изображение в зависимости от типа
        switch ($type) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($sourcePath);
                break;
            default:
                return false; // Неподдерживаемый тип изображения
        }

        // Создаем новое изображение
        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        // Для PNG и GIF сохраняем прозрачность
        if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
            imagecolortransparent($newImage, imagecolorallocatealpha($newImage, 0, 0, 0, 127));
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
        }

        // Масштабируем изображение
        imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

        // Сохраняем изображение
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($newImage, $targetPath, 90); // 90% качество
                break;
            case IMAGETYPE_PNG:
                imagepng($newImage, $targetPath, 9); // Уровень сжатия 9
                break;
            case IMAGETYPE_GIF:
                imagegif($newImage, $targetPath);
                break;
        }

        // Освобождаем память
        imagedestroy($sourceImage);
        imagedestroy($newImage);

        return true;
    }

}