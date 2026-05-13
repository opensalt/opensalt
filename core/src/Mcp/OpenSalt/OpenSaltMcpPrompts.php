<?php

declare(strict_types=1);

namespace App\Mcp\OpenSalt;

use Mcp\Capability\Attribute\McpPrompt;

class OpenSaltMcpPrompts
{
    /**
     * @return list<array{role: string, content: string}>
     */
    #[McpPrompt(name: 'opensalt_usage_guide')]
    public function usageGuidePrompt(): array
    {
        return [
            [
                'role' => 'user',
                'content' => 'Use OpenSALT MCP tools to inspect framework documents, fetch item details, and discover related items with association and vector matching.',
            ],
            [
                'role' => 'user',
                'content' => 'When sharing findings, include document/item identifiers and summarize why a related item matched.',
            ],
        ];
    }
}
