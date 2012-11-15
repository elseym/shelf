<?php

namespace elseym\ShelfBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table
 * @ORM\Entity
 */

class Book implements \elseym\AgatheBundle\AgatheExtendedResourceInterface
{
    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=127)
     */
    private $title;

    /**
     * @var string
     * @ORM\Column(type="string", length=127)
     */
    private $author;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $year;

    /**
     * @var string
     * @ORM\Column(type="string", length=191, unique=true)
     * @GEDMO\Slug(fields={"title"})
     */
    private $slug;

    function __construct() {
    }

    /**
     * @param string $author
     */
    public function setAuthor($author) {
        $this->author = $author;
    }

    /**
     * @return string
     */
    public function getAuthor() {
        return $this->author;
    }

    /**
     * @return string
     */
    public function getSlug() {
        return $this->slug;
    }

    /**
     * @param int $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param string $title
     */
    public function setTitle($title) {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @param int $year
     */
    public function setYear($year) {
        $this->year = min(max(0, $year), intval(date("Y")) + 1);
    }

    /**
     * @return int
     */
    public function getYear() {
        return $this->year;
    }

    public function getPayload()
    {
        return get_object_vars($this);
    }

    public function getCommand()
    {
        return "";
    }

    /**
     * @return string
     */
    public function getResourceId()
    {
        $slug = $this->getSlug();

        return "/book/" . trim($this->getSlug(), "/");
    }
}