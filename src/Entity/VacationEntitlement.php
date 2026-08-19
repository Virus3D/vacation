<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\VacationEntitlementRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VacationEntitlementRepository::class)]
class VacationEntitlement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'vacationEntitlements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Employee $employee = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: 'integer')]
    private ?int $days = null;

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

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }// end getStartDate()

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }// end setStartDate()

    public function getDays(): ?int
    {
        return $this->days;
    }// end getDays()

    public function setDays(int $days): static
    {
        $this->days = $days;
        return $this;
    }// end setDays()
}// end class
