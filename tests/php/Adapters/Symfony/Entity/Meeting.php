<?php

declare(strict_types=1);

namespace Celerik\Ui\Tests\Adapters\Symfony\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'meetings')]
class Meeting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    public function __construct(
        #[ORM\Column(length: 120)]
        public string $title,
        #[ORM\Column]
        public DateTimeImmutable $startsAt,
        #[ORM\Column(nullable: true)]
        public ?DateTimeImmutable $endsAt = null,
        #[ORM\Column]
        public bool $allDay = false,
        #[ORM\Column(length: 20, nullable: true)]
        public ?string $color = null,
    ) {
    }
}
