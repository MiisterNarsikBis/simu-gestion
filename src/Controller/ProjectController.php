<?php

namespace App\Controller;

use App\Entity\ProjectAssignment;
use App\Repository\CompanyRepository;
use App\Repository\EmployeeRepository;
use App\Repository\ProjectAssignmentRepository;
use App\Repository\ProjectRepository;
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
        CompanyRepository $companyRepository,
        EmployeeRepository $employeeRepository,
        ProjectAssignmentRepository $projectAssignmentRepository,
        ProjectProgressService $projectProgressService
    ): Response {
        $company = $companyRepository->findOneBy([]);
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
        CompanyRepository $companyRepository,
        ProjectSeedService $projectSeedService,
        ProjectProgressService $projectProgressService
    ): Response {
        $company = $companyRepository->findOneBy([]);
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
            $entityManager->flush();
            $this->addFlash('success', sprintf('Employé %s retiré du projet', $employee->getName()));
        }

        return $this->redirectToRoute('app_projects');
    }
}
