<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\VacationDetailRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VacationDetailRepository::class)]
class VacationDetail
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'vacationDetails')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Vacation $vacation = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $workYearStart = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $workYearEnd = null;

    #[ORM\Column(type: 'integer')]
    private ?int $daysUsed = null;

    // 'main' or 'additional'
    #[ORM\Column(length: 20)]
    private ?string $vacationType = null;

    public function getId(): ?int
    {
        return $this->id;
    }// end getId()

    public function getVacation(): ?Vacation
    {
        return $this->vacation;
    }// end getVacation()

    public function setVacation(?Vacation $vacation): static
    {
        $this->vacation = $vacation;
        return $this;
    }// end setVacation()

    public function getWorkYearStart(): ?\DateTimeInterface
    {
        return $this->workYearStart;
    }// end getWorkYearStart()

    public function setWorkYearStart(\DateTimeInterface $workYearStart): static
    {
        $this->workYearStart = $workYearStart;
        return $this;
    }// end setWorkYearStart()

    public function getWorkYearEnd(): ?\DateTimeInterface
    {
        return $this->workYearEnd;
    }// end getWorkYearEnd()

    public function setWorkYearEnd(\DateTimeInterface $workYearEnd): static
    {
        $this->workYearEnd = $workYearEnd;
        return $this;
    }// end setWorkYearEnd()

    public function getDaysUsed(): ?int
    {
        return $this->daysUsed;
    }// end getDaysUsed()

    public function setDaysUsed(int $daysUsed): static
    {
        $this->daysUsed = $daysUsed;
        return $this;
    }// end setDaysUsed()

    public function getVacationType(): ?string
    {
        return $this->vacationType;
    }// end getVacationType()

    public function setVacationType(string $vacationType): static
    {
        $this->vacationType = $vacationType;
        return $this;
    }// end setVacationType()
}// end class
