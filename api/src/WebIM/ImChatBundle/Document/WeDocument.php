<?php

namespace WebIM\ImChatBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document
 */
class WeDocument
{
    /**
     * @MongoDB\Id
     */
    protected $id;
    /**
     * @MongoDB\String
     */
    protected $name;
    /**
     * @MongoDB\Int
     */
    protected $length;
    /**
     * @MongoDB\Date
     */
    protected $uploadDate;
    /**
     * @MongoDB\Int
     */
    protected $chunkSize;
    /**
     * @MongoDB\String
     */
    protected $md5;  
    /**
     * @MongoDB\File
     */
    protected $file;

    /**
     * Get id
     *
     * @return id $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return WeDocument
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get name
     *
     * @return string $name
     */
    public function getName()
    {
        return $this->name;
    }

//ÓÉdoctrine.odmÌîÐ´
//    /**
//     * Set length
//     *
//     * @param int $length
//     * @return WeDocument
//     */
//    public function setLength($length)
//    {
//        $this->length = $length;
//        return $this;
//    }

    /**
     * Get length
     *
     * @return int $length
     */
    public function getLength()
    {
        return $this->length;
    }

//ÓÉdoctrine.odmÌîÐ´
//    /**
//     * Set uploadDate
//     *
//     * @param date $uploadDate
//     * @return WeDocument
//     */
//    public function setUploadDate($uploadDate)
//    {
//        $this->uploadDate = $uploadDate;
//        return $this;
//    }

    /**
     * Get uploadDate
     *
     * @return date $uploadDate
     */
    public function getUploadDate()
    {
        return $this->uploadDate;
    }

//ÓÉdoctrine.odmÌîÐ´
//    /**
//     * Set chunkSize
//     *
//     * @param int $chunkSize
//     * @return WeDocument
//     */
//    public function setChunkSize($chunkSize)
//    {
//        $this->chunkSize = $chunkSize;
//        return $this;
//    }

    /**
     * Get chunkSize
     *
     * @return int $chunkSize
     */
    public function getChunkSize()
    {
        return $this->chunkSize;
    }

//ÓÉdoctrine.odmÌîÐ´
//    /**
//     * Set md5
//     *
//     * @param string $md5
//     * @return WeDocument
//     */
//    public function setMd5($md5)
//    {
//        $this->md5 = $md5;
//        return $this;
//    }

    /**
     * Get md5
     *
     * @return string $md5
     */
    public function getMd5()
    {
        return $this->md5;
    }

    /**
     * Set file
     *
     * @param file $file
     * @return WeDocument
     */
    public function setFile($file)
    {
        $this->file = $file;
        return $this;
    }

    /**
     * Get file
     *
     * @return file $file
     */
    public function getFile()
    {
        return $this->file;
    }
}
