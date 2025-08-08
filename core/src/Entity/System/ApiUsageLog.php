<?php

declare(strict_types=1);

namespace App\Entity\System;

use App\Repository\System\ApiUsageLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApiUsageLogRepository::class)]
#[ORM\Table(name: 'api_usage_log')]
#[ORM\Index(name: 'api_usage_log_created_at_idx', columns: ['created_at'])]
#[ORM\Index(name: 'api_usage_log_user_idx', columns: ['user_identifier'])]
#[ORM\Index(name: 'api_usage_log_endpoint_idx', columns: ['endpoint_url'], options: ['lengths' => [255]])]
class ApiUsageLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public private(set) ?int $id = null;

    #[ORM\Column(name: 'created_at', type:  Types::DATETIMETZ_IMMUTABLE, precision: 6)]
    public private(set) \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'user_identifier', length: 255)]
    public private(set) string $userIdentifier;

    #[ORM\Column(name: 'api_token_id', nullable: true)]
    public private(set) ?int $apiTokenId = null;

    #[ORM\Column(length: 45, nullable: true)]
    public private(set) ?string $ip = null;

    #[ORM\Column(length: 10)]
    public private(set) string $method;

    #[ORM\Column(type: Types::TEXT)]
    public private(set) string $request;

    #[ORM\Column(name: 'endpoint_url', type: Types::STRING, length: 2048)]
    public private(set) string $endpointUrl;

    #[ORM\Column(name: 'status_code', type: Types::SMALLINT)]
    public private(set) int $statusCode;

    #[ORM\Column(name: 'request_full', type: Types::JSON, nullable: true)]
    public private(set) ?array $requestFull = null;

    public function __construct(
        string $userIdentifier,
        ?int $apiTokenId,
        ?string $ip,
        string $method,
        string $request,
        string $endpointUrl,
        int $statusCode,
        ?\DateTimeImmutable $createdAt = null,
        ?array $requestFull = null,
    ) {
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
        $this->userIdentifier = $userIdentifier;
        $this->apiTokenId = $apiTokenId;
        $this->ip = $ip;
        $this->method = $method;
        $this->request = $request;
        $this->endpointUrl = $endpointUrl;
        $this->statusCode = $statusCode;
        $this->requestFull = $requestFull;
    }
}
