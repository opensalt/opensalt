<?php

namespace App\Service;

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Psr\Log\LoggerInterface;

class FirebaseFactory
{
    public static function createFirebase(
        LoggerInterface $logger,
        ?string $projectId,
        ?string $clientId,
        ?string $clientEmail,
        ?string $privateKey,
        ?string $apiKey,
        ?string $dbUri
    )
    {
        if (null === $projectId) {
            return null;
        }

        $pKey = preg_replace('/\\\\n/', chr(10), $privateKey);
        //$pKey = preg_replace('/-----(BEGIN|END) PRIVATE KEY-----/', '', $pKey);

        $serviceAccount = ServiceAccount::fromArray([
            'project_id' => $projectId,
            'client_id' => $clientId,
            'client_email' => $clientEmail,
            'private_key' => $pKey,
        ]);

        return (new Factory())
            ->withServiceAccountAndApiKey($serviceAccount, $apiKey)
            ->withDatabaseUri($dbUri)
            ->create();
    }
}
