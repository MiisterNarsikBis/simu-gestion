<?php

namespace App\Controller;

use App\Entity\Employee;
use App\Entity\ProjectAssignment;
use App\Repository\EmployeeRepository;
use App\Repository\ProjectAssignmentRepository;
use App\Repository\ProjectRepository;
use App\Service\CompanyProvider;
use App\Service\ProjectProgressService;
use App\Service\ProjectSeedService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProjectController extends AbstractController
{
    #[Route('/projects', name: 'app_projects')]
    public function index(
        ProjectRepository $projectRepository,
        CompanyProvider $companyProvider,
        EmployeeRepository $employeeRepository,
        ProjectAssignmentRepository $projectAssignmentRepository,
        ProjectProgressService $projectProgressService
    ): Response {
        $company = $companyProvider->getCompany();
        if (!$company) {
            return $this->redirectToRoute('app_onboarding');
        }

        $projects = $projectRepository->findInProgressByCompany($company->getId());
        $employees = $employeeRepository->findByCompany($company->getId());
        
        // Forcer l'auto-assignment pour tous les projets qui n'ont pas d'assignations
        foreach ($projects as $project) {
            $existingAssignments = $projectAssignmentRepository->findByProjectAndStage(
                $project->getId(),
                $project->getPipelineStage()
            );
            if (empty($existingAssignments) && $project->getRequiredRole() !== null) {
                $projectProgressService->autoAssignEmployee($project);
            }
        }
        
        // Recharger les projets pour avoir les assignations à jour
        $projects = $projectRepository->findInProgressByCompany($company->getId());
        
        // Récupérer les assignations pour chaque projet
        $assignmentsByProject = [];
        foreach ($projects as $project) {
            $assignmentsByProject[$project->getId()] = $projectAssignmentRepository->findByProjectAndStage(
                $project->getId(),
                $project->getPipelineStage()
            );
        }

        return $this->render('project/index.html.twig', [
            'projects' => $projects,
            'employees' => $employees,
            'assignmentsByProject' => $assignmentsByProject,
        ]);
    }

    #[Route('/projects/generate', name: 'app_projects_generate', methods: ['POST'])]
    public function generate(
        CompanyProvider $companyProvider,
        ProjectSeedService $projectSeedService,
        ProjectProgressService $projectProgressService
    ): Response {
        $company = $companyProvider->getCompany();
        if (!$company) {
            return $this->redirectToRoute('app_onboarding');
        }

        $result = $projectSeedService->generateClientWithProject($company);
        
        // Auto-assigner si possible
        $projectProgressService->autoAssignEmployee($result['project']);

        $this->addFlash('success', 'Nouveau client et projet générés !');
        return $this->redirectToRoute('app_projects');
    }

    #[Route('/projects/{id}/assign', name: 'app_projects_assign', methods: ['POST'])]
    public function assignEmployee(
        int $id,
        Request $request,
        ProjectRepository $projectRepository,
        EmployeeRepository $employeeRepository,
        ProjectAssignmentRepository $projectAssignmentRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $project = $projectRepository->find($id);
        if (!$project) {
            $this->addFlash('error', 'Projet introuvable');
            return $this->redirectToRoute('app_projects');
        }

        $employeeId = $request->request->getInt('employee_id', 0);
        $employee = $employeeRepository->find($employeeId);
        
        if (!$employee) {
            $this->addFlash('error', 'Employé introuvable');
            return $this->redirectToRoute('app_projects');
        }

        // Vérifier que l'employé a le bon rôle pour l'étape actuelle
        $requiredRole = $project->getRequiredRole();
        if ($requiredRole !== null && $employee->getRole() !== $requiredRole) {
            $this->addFlash('error', sprintf('Cet employé (%s) ne peut pas travailler sur l\'étape %s (nécessite %s)', $employee->getRole(), $project->getPipelineStage(), $requiredRole));
            return $this->redirectToRoute('app_projects');
        }

        // Vérifier que l'employé est disponible
        if (!$employee->isAvailable()) {
            $this->addFlash('error', 'Cet employé n\'est pas disponible (en formation ou déjà sur un projet)');
            return $this->redirectToRoute('app_projects');
        }

        // Vérifier si l'assignation existe déjà
        $existingAssignment = $projectAssignmentRepository->findOneBy([
            'project' => $project,
            'employee' => $employee,
            'stage' => $project->getPipelineStage(),
        ]);

        if ($existingAssignment) {
            $this->addFlash('info', 'Cet employé est déjà assigné à ce projet sur cette étape');
            return $this->redirectToRoute('app_projects');
        }

        // Créer l'assignation
        $assignment = new ProjectAssignment();
        $assignment->setProject($project);
        $assignment->setEmployee($employee);
        $assignment->setStage($project->getPipelineStage());
        $assignment->setAllocation(100);

        $employee->setAvailabilityStatus(Employee::STATUS_SUR_POSTE);

        $entityManager->persist($assignment);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Employé %s assigné au projet !', $employee->getName()));
        return $this->redirectToRoute('app_projects');
    }

    #[Route('/projects/{id}/unassign/{employeeId}', name: 'app_projects_unassign', methods: ['POST'])]
    public function unassignEmployee(
        int $id,
        int $employeeId,
        ProjectRepository $projectRepository,
        EmployeeRepository $employeeRepository,
        ProjectAssignmentRepository $projectAssignmentRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $project = $projectRepository->find($id);
        if (!$project) {
            $this->addFlash('error', 'Projet introuvable');
            return $this->redirectToRoute('app_projects');
        }

        $employee = $employeeRepository->find($employeeId);
        if (!$employee) {
            $this->addFlash('error', 'Employé introuvable');
            return $this->redirectToRoute('app_projects');
        }

        // Trouver l'assignation
        $assignment = $projectAssignmentRepository->findOneBy([
            'project' => $project,
            'employee' => $employee,
            'stage' => $project->getPipelineStage(),
        ]);

        if ($assignment) {
            $entityManager->remove($assignment);
            $employee->setAvailabilityStatus(Employee::STATUS_DISPO);
            $entityManager->flush();
            $this->addFlash('success', sprintf('Employé %s retiré du projet', $employee->getName()));
        }

        return $this->redirectToRoute('app_projects');
    }

    #[Route('/projects/{id}/auto-assign', name: 'app_projects_auto_assign', methods: ['POST'])]
    public function autoAssign(
        int $id,
        Request $request,
        ProjectRepository $projectRepository,
        EmployeeRepository $employeeRepository,
        ProjectAssignmentRepository $projectAssignmentRepository,
        CompanyProvider $companyProvider,
        EntityManagerInterface $entityManager
    ): Response {
        $project = $projectRepository->find($id);
        if (!$project) {
            $this->addFlash('error', 'Projet introuvable');
            return $this->redirectToRoute('app_projects');
        }

        $company = $companyProvider->getCompany();
        if (!$company) {
            return $this->redirectToRoute('app_onboarding');
        }

        $role = $request->request->getString('role');
        $validRoles = [
            Employee::ROLE_DEV,
            Employee::ROLE_DESIGNER,
            Employee::ROLE_GRAPHISTE,
            Employee::ROLE_INTEGRATEUR,
            Employee::ROLE_RH,
            Employee::ROLE_MANAGER,
        ];

        if (!in_array($role, $validRoles, true)) {
            $this->addFlash('error', 'Rôle invalide');
            return $this->redirectToRoute('app_projects');
        }

        // Récupérer les IDs déjà assignés sur ce projet/stage
        $existingAssignments = $projectAssignmentRepository->findByProjectAndStage(
            $project->getId(),
            $project->getPipelineStage()
        );
        $assignedEmployeeIds = array_map(
            fn($a) => $a->getEmployee()->getId(),
            $existingAssignments
        );

        // Trouver le premier employé disponible avec le bon rôle, non déjà assigné
        $availableEmployees = $employeeRepository->findAvailableByCompany($company->getId());
        $chosen = null;
        foreach ($availableEmployees as $emp) {
            if ($emp->getRole() === $role && !in_array($emp->getId(), $assignedEmployeeIds, true)) {
                $chosen = $emp;
                break;
            }
        }

        if ($chosen === null) {
            $this->addFlash('error', sprintf('Aucun employé disponible avec le rôle %s', $role));
            return $this->redirectToRoute('app_projects');
        }

        $assignment = new ProjectAssignment();
        $assignment->setProject($project);
        $assignment->setEmployee($chosen);
        $assignment->setStage($project->getPipelineStage());
        $assignment->setAllocation(100);

        $chosen->setAvailabilityStatus(Employee::STATUS_SUR_POSTE);

        $entityManager->persist($assignment);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Employé %s auto-assigné au projet !', $chosen->getName()));
        return $this->redirectToRoute('app_projects');
    }
}
