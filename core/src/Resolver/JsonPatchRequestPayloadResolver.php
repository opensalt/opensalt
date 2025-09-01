<?php

declare(strict_types=1);

namespace App\Resolver;

use App\DTO\Api\V1\PatchDto;
use App\DTO\Api\V1\PatchOperation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsTargetedValueResolver()]
readonly class JsonPatchRequestPayloadResolver implements ValueResolverInterface
{
    public function __construct(
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        var_dump('ARGUMENT_TYPE', $argument->getType());
        return count($argument->getAttributes(MapRequestPayload::class, ArgumentMetadata::IS_INSTANCEOF)) > 0
            && PatchDto::class === $argument->getType();
    }

    #[\Override]
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        // Check content type
        $contentType = $request->headers->get('Content-Type');
        if ('application/json-patch+json' !== $contentType) {
            throw new BadRequestHttpException('Content-Type must be application/json-patch+json');
        }

        $content = $request->getContent();
        if (empty($content)) {
            throw new BadRequestHttpException('Request body cannot be empty');
        }

        $operations = $this->serializer->deserialize($content, PatchOperation::class.'[]', 'json', [
        ]);

        $dto = new PatchDto();
        $dto->patch = $operations;

        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            throw new ValidationFailedException('Patch was not valid', $violations);
        }

        yield $dto;
    }
}
