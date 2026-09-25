<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\NonAccrualPeriodRepository;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NonAccrualPeriodRepository::class)]
class NonAccrualPeriod
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'nonAccrualPeriods')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Employee $employee = null;

    #[ORM\Column(type: 'date')]
    private ?DateTimeInterface $startDate = null;

    #[ORM\Column(type: 'date')]
    private ?DateTimeInterface $endDate = null;

    /**
     * Value: parental, unpaid, other
     */
    #[ORM\Column(length: 50)]
    private string $type = 'parental';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $comment = null;

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

    public function getType(): string
    {
        return $this->type;
    }// end getType()

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }// end setType()

    public function getComment(): ?string
    {
        return $this->comment;
    }// end getComment()

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;
        return $this;
    }// end setComment()
}// end class
