<?php

namespace App\Service;

use App\Entity\Company;
use App\Repository\CompanyRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class CompanyProvider
{
    private const SESSION_KEY = 'company_id';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly CompanyRepository $companyRepository
    ) {
    }

    public function getCompany(): ?Company
    {
        $session = $this->requestStack->getSession();
        $companyId = $session->get(self::SESSION_KEY);

        if (!$companyId) {
            return null;
        }

        return $this->companyRepository->find($companyId);
    }

    public function setCompany(Company $company): void
    {
        $session = $this->requestStack->getSession();
        $session->set(self::SESSION_KEY, $company->getId());
    }
}
