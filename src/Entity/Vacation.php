<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\VacationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VacationRepository::class)]
class Vacation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'vacations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Employee $employee = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'vacation', targetEntity: VacationDetail::class, cascade: ['persist', 'remove'])]
    private Collection $vacationDetails;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->vacationDetails = new ArrayCollection();
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

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }// end getStartDate()

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }// end setStartDate()

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }// end getEndDate()

    public function setEndDate(\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }// end setEndDate()

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }// end getCreatedAt()

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }// end setCreatedAt()

    /**
     * @return Collection<int, VacationDetail>
     */
    public function getVacationDetails(): Collection
    {
        return $this->vacationDetails;
    }// end getVacationDetails()

    public function addVacationDetail(VacationDetail $vacationDetail): static
    {
        if (!$this->vacationDetails->contains($vacationDetail)) {
            $this->vacationDetails->add($vacationDetail);
            $vacationDetail->setVacation($this);
        }
        return $this;
    }// end addVacationDetail()

    public function removeVacationDetail(VacationDetail $vacationDetail): static
    {
        if ($this->vacationDetails->removeElement($vacationDetail)) {
            if ($vacationDetail->getVacation() === $this) {
                $vacationDetail->setVacation(null);
            }
        }
        return $this;
    }// end removeVacationDetail()

    public function getTotalDays(): int
    {
        $interval = $this->startDate->diff($this->endDate);
        return $interval->days + 1;
    }// end getTotalDays()
}// end class
