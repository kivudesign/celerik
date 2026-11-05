<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\MeetingRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MeetingRepository::class)]
#[ORM\Index(columns: ['starts_at'])]
class Meeting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function __construct(
        #[ORM\Column(length: 255)]
        private string $title,
        #[ORM\Column]
        private DateTimeImmutable $startsAt,
        #[ORM\Column(nullable: true)]
        private ?DateTimeImmutable $endsAt = null,
        #[ORM\Column]
        private bool $allDay = false,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function reschedule(
        string $title,
        DateTimeImmutable $startsAt,
        ?DateTimeImmutable $endsAt,
        bool $allDay,
    ): void {
        $this->title = $title;
        $this->startsAt = $startsAt;
        $this->endsAt = $endsAt;
        $this->allDay = $allDay;
    }
}
