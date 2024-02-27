<?php

namespace App\Service;

use App\Entity\UserOperation;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Validation;

class DataLoader implements DataLoaderInterface
{
    public const CSV_DELIMITER = 'delimiter';
    public const CSV_HEADERS = 'headers';
    public const DECIMAL_COL = 'decimal_column';
    public const DECIMAL_DELIMITER = 'decimal_delimiter';

    protected string $filePath;

    public function __construct(
        #[Autowire('%app.csv_configuration%')]
        protected array $config
    ){}

    public function setSourcePath(string $path): void
    {
        $this->filePath = $path;
    }

    /**
     * @return array<UserOperation> Array of entitiess.
     */
    public function load(): array
    {
        $this->validateConfig($this->config);

        $rows = file($this->filePath);
        
        return $this->parseCsv($rows);
    }

    /**
     * @throws ValidatorException
     */
    public function validateConfig(array $config): void
    {
        // Create a validator
        $validator = Validation::createValidator();

        // Define constraints
        $constraints = new Assert\Collection([
            self::CSV_HEADERS       => [new Assert\NotBlank(), new Assert\Type('string')],
            self::CSV_DELIMITER     => [new Assert\NotBlank(), new Assert\Type('string')],
            self::DECIMAL_DELIMITER => [new Assert\NotBlank(), new Assert\Type('string')],
            self::DECIMAL_COL       => [new Assert\NotBlank(), new Assert\Type('string')],
        ]);

        // Validate the array against the constraints
        $violations = $validator->validate($config, $constraints);

        // Check if there are any violations
        if (count($violations) > 0) {
            foreach ($violations as $violation) {
                // Handle or log the validation error messages
                echo $violation->getPropertyPath() . ': ' . $violation->getMessage() . PHP_EOL;
            }

            throw new ValidatorException("Invalid configuration of properties in service.yaml");
        }
    }

    /**
     * Reads the number of symbols after the dot to ddetermine currency precision
     * Ex.: 100.00 has 2 zeroes after "." means presicion of ccurrency is 2.
     */
    protected function getPrecision(string $value): int
    {
        $parts = explode('.', $value);

        if (count($parts) === 2) {
            return strlen($parts[1]);
        }

        return 0;
    }

    /** 
     *  @return array<UserOperation> Array of entities.
     */
    protected function parseCsv(array $rows) : array
    {
        $data = [];

        foreach ($rows as $row) {
            $rowData = explode($this->config[self::CSV_DELIMITER], $row);
            $data[] = $this->deserialize($rowData, UserOperation::class);
        }

        return $data;
    }

    /**
     * Maps data from .csv file to <UserOperation> object's properties.
     * @throws \InvalidArgumentException
     */
    protected function deserialize(array $row, string $object): UserOperation
    {
        /** @var UserOperation $instance */
        $instance = new $object();
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $headers = explode(',', $this->config[self::CSV_HEADERS]);

        foreach ($headers as $key => $column) {
            if (!array_key_exists($key, $row)) {
                throw new \InvalidArgumentException(sprintf('Column %s at position %s not found. Check header configuration.', $column, $key));
            }

            $propertyAccessor->setValue($instance, $column, trim($row[$key]));

            if ($column === $this->config[self::DECIMAL_COL]) {
                $instance->setPrecision($this->getPrecision(trim($row[$key])));
            }
        }
        
        return $instance;
    }
}
