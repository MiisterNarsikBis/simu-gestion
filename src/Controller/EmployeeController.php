<?php

namespace App\Controller;

use App\Entity\Employee;
use App\Repository\CompanyRepository;
use App\Repository\EmployeeRepository;
use App\Repository\TrainingRepository;
use App\Service\EmployeeNameGenerator;
use App\Service\TrainingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EmployeeController extends AbstractController
{
    #[Route('/employees', name: 'app_employees')]
    public function index(
        EmployeeRepository $employeeRepository,
        TrainingRepository $trainingRepository,
        CompanyRepository $companyRepository
    ): Response {
        // Pour l'instant, on récupère la première entreprise
        $company = $companyRepository->findOneBy([]);
        if (!$company) {
            return $this->redirectToRoute('app_onboarding');
        }

        $employees = $employeeRepository->findByCompany($company->getId());
        $activeTrainings = $trainingRepository->findActiveByCompany($company->getId());

        return $this->render('employee/index.html.twig', [
            'employees' => $employees,
            'activeTrainings' => $activeTrainings,
        ]);
    }

    #[Route('/employees/create', name: 'app_employees_create', methods: ['POST'])]
    public function create(
        Request $request,
        CompanyRepository $companyRepository,
        EntityManagerInterface $entityManager,
        EmployeeNameGenerator $nameGenerator
    ): Response {
        $company = $companyRepository->findOneBy([]);
        if (!$company) {
            return $this->redirectToRoute('app_onboarding');
        }

        $role = $request->request->get('role', Employee::ROLE_DEV);
        $count = $request->request->getInt('count', 1);
        
        // Limiter à 10 employés maximum
        $count = max(1, min(10, $count));
        
        // Définir le salaire de base selon le rôle
        $baseSalaries = [
            Employee::ROLE_DEV => '120.00',
            Employee::ROLE_DESIGNER => '110.00',
            Employee::ROLE_GRAPHISTE => '100.00',
            Employee::ROLE_INTEGRATEUR => '105.00',
            Employee::ROLE_RH => '90.00',
            Employee::ROLE_MANAGER => '150.00',
        ];
        $salaryDaily = $baseSalaries[$role] ?? '100.00';

        $createdNames = [];
        
        // Créer plusieurs employés
        for ($i = 0; $i < $count; $i++) {
            // Générer un nom automatiquement
            $name = $nameGenerator->generate();
            
            $employee = new Employee();
            $employee->setCompany($company);
            $employee->setName($name);
            $employee->setRole($role);
            $employee->setSalaryDaily($salaryDaily);
            $employee->setTrainingStars(1);
            $employee->setAvailabilityStatus(Employee::STATUS_DISPO);

            $entityManager->persist($employee);
            $createdNames[] = $name;
        }

        $entityManager->flush();

        if ($count === 1) {
            $this->addFlash('success', sprintf('Employé %s recruté avec succès !', $createdNames[0]));
        } else {
            $this->addFlash('success', sprintf('%d employés recrutés avec succès !', $count));
        }
        
        return $this->redirectToRoute('app_employees');
    }

    #[Route('/employees/{id}/training/start', name: 'app_employees_training_start', methods: ['POST'])]
    public function startTraining(
        int $id,
        Request $request,
        EmployeeRepository $employeeRepository,
        TrainingService $trainingService
    ): Response {
        $employee = $employeeRepository->find($id);
        if (!$employee) {
            $this->addFlash('error', 'Employé introuvable.');
            return $this->redirectToRoute('app_employees');
        }

        $targetStars = $request->request->getInt('targetStars', 2);
        $daysTotal = $request->request->getInt('daysTotal', 5);
        $cost = $request->request->get('cost', '500.00');

        try {
            $trainingService->startTraining($employee, $targetStars, $daysTotal, $cost);
            $this->addFlash('success', 'Formation démarrée avec succès !');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_employees');
    }
}
