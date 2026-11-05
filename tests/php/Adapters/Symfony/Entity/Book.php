<?php

declare(strict_types=1);

namespace Celerik\Ui\Tests\Adapters\Symfony\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'books')]
class Book
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    public function __construct(
        #[ORM\Column(length: 120)]
        public string $title,
        #[ORM\Column(length: 40)]
        public string $genre,
        #[ORM\Column]
        public int $pages,
        #[ORM\ManyToOne(targetEntity: Author::class)]
        #[ORM\JoinColumn(nullable: false)]
        public Author $author,
    ) {
    }
}
