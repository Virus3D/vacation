<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\EmployeeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmployeeRepository::class)]
class Employee
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $fullName = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $hireDate = null;

    #[ORM\Column(type: 'integer')]
    private ?int $baseVacationDays = 28;

    #[ORM\Column(type: 'integer')]
    private ?int $additionalVacationDays = 0;

    #[ORM\OneToMany(mappedBy: 'employee', targetEntity: Vacation::class, cascade: ['persist', 'remove'])]
    private Collection $vacations;

    #[ORM\OneToMany(mappedBy: 'employee', targetEntity: VacationEntitlement::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $vacationEntitlements;

    public function __construct()
    {
        $this->vacations = new ArrayCollection();
        $this->vacationEntitlements = new ArrayCollection();
    }// end __construct()

    public function getId(): ?int
    {
        return $this->id;
    }// end getId()

    public function getFullName(): ?string
    {
        return $this->fullName;
    }// end getFullName()

    public function setFullName(string $fullName): static
    {
        $this->fullName = $fullName;
        return $this;
    }// end setFullName()

    public function getHireDate(): ?\DateTimeInterface
    {
        return $this->hireDate;
    }// end getHireDate()

    public function setHireDate(\DateTimeInterface $hireDate): static
    {
        $this->hireDate = $hireDate;
        return $this;
    }// end setHireDate()

    public function getBaseVacationDays(): ?int
    {
        return $this->baseVacationDays;
    }// end getBaseVacationDays()

    public function setBaseVacationDays(int $baseVacationDays): static
    {
        $this->baseVacationDays = $baseVacationDays;
        return $this;
    }// end setBaseVacationDays()

    public function getAdditionalVacationDays(): ?int
    {
        return $this->additionalVacationDays;
    }// end getAdditionalVacationDays()

    public function setAdditionalVacationDays(int $additionalVacationDays): static
    {
        $this->additionalVacationDays = $additionalVacationDays;
        return $this;
    }// end setAdditionalVacationDays()

    /**
     * @return Collection<int, Vacation>
     */
    public function getVacations(): Collection
    {
        return $this->vacations;
    }// end getVacations()

    public function addVacation(Vacation $vacation): static
    {
        if (!$this->vacations->contains($vacation)) {
            $this->vacations->add($vacation);
            $vacation->setEmployee($this);
        }
        return $this;
    }// end addVacation()

    public function removeVacation(Vacation $vacation): static
    {
        if ($this->vacations->removeElement($vacation)) {
            if ($vacation->getEmployee() === $this) {
                $vacation->setEmployee(null);
            }
        }
        return $this;
    }// end removeVacation()

    /**
     * @return Collection<int, VacationEntitlement>
     */
    public function getVacationEntitlements(): Collection
    {
        return $this->vacationEntitlements;
    }// end getVacationEntitlements()

    public function addVacationEntitlement(VacationEntitlement $entitlement): static
    {
        if (!$this->vacationEntitlements->contains($entitlement)) {
            $this->vacationEntitlements->add($entitlement);
            $entitlement->setEmployee($this);
        }
        return $this;
    }// end addVacationEntitlement()

    public function removeVacationEntitlement(VacationEntitlement $entitlement): static
    {
        if ($this->vacationEntitlements->removeElement($entitlement)) {
            if ($entitlement->getEmployee() === $this) {
                $entitlement->setEmployee(null);
            }
        }
        return $this;
    }// end removeVacationEntitlement()

    /**
     * Возвращает фиксированные дополнительные дни отпуска на указанную дату.
     */
    public function getFixedAdditionalDaysForDate(\DateTimeInterface $date): int
    {
        $days = 0;
        foreach ($this->vacationEntitlements as $entitlement) {
            if ($entitlement->getStartDate() <= $date) {
                $days = $entitlement->getDays();
// берём последнее по дате
            } else {
                break;
// т.к. коллекция не гарантирует сортировку, лучше отсортировать
            }
        }
        // Альтернативно: отсортировать коллекцию по startDate и взять последнюю <= date
        return $days;
    }// end getFixedAdditionalDaysForDate()
}// end class
