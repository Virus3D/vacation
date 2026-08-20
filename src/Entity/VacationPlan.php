<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\VacationPlanRepository;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VacationPlanRepository::class)]
class VacationPlan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Employee::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Employee $employee = null;

    #[ORM\Column(type: 'date')]
    private ?DateTimeInterface $startDate = null;

    #[ORM\Column(type: 'date')]
    private ?DateTimeInterface $endDate = null;

    // Value: planned, approved, rejected.
    #[ORM\Column(length: 20)]
    private string $status = 'planned';

    #[ORM\Column(type: 'datetime')]
    private ?DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }// end __construct()

    public function getId(): ?int
    {
        return $this->id;
    }// end getId()

    public function getEmployee(): ?Employee
    {
        return $this->employee;
    }// end getEmployee()

    public function setEmployee(?Employee $employee): static
    {
        $this->employee = $employee;
        return $this;
    }// end setEmployee()

    public function getStartDate(): ?DateTimeInterface
    {
        return $this->startDate;
    }// end getStartDate()

    public function setStartDate(DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }// end setStartDate()

    public function getEndDate(): ?DateTimeInterface
    {
        return $this->endDate;
    }// end getEndDate()

    public function setEndDate(DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }// end setEndDate()

    public function getStatus(): string
    {
        return $this->status;
    }// end getStatus()

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }// end setStatus()

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }// end getCreatedAt()

    public function setCreatedAt(DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }// end setCreatedAt()

    public function getTotalDays(): int
    {
        $interval = $this->startDate->diff($this->endDate);
        return $interval->days + 1;
    }// end getTotalDays()
}// end class
