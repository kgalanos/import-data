<?php

namespace Kgalanos\ImportData;

class ImportDataClass
{
    protected $streamErrors;

    protected int $problemRec = 0;

    public function __construct(
        /**
         * @var Model $model
         */
        protected string $model,
        public array $data,
        string $folderToSaveErrors = null,
    ) {
        $tableName = str_replace('\\', '_', $model);
        $this->streamErrors = fopen($folderToSaveErrors.$tableName.'err.txt', 'w');
    }

    public function __destruct()
    {
        fclose($this->streamErrors);
    }

    public static function make(
        string $model,
        array $data,
        string $folderToSaveErrors = null,
    ): self {
        return new self($model, $data, $folderToSaveErrors);
    }

    public function getData(array $data)
    {
        $this->data = $data;
    }

    public function getProblemRec(): int
    {
        return $this->problemRec;
    }

    public function insert()
    {
        $this->problemRec = 0;
        try {
            $this->model::insert($this->data);
        } catch (\Exception $exception) {
            /*
             * if there is any error examine one by one rec
             */
            foreach ($this->data as $rec) {
                $this->create($rec);
            }
        }

        return true;
    }

    private function create($rec)
    {
        try {
            $this->model::create($rec);
        } catch (\Exception $exception) {
            $this->problemRec++;
            fwrite($this->streamErrors, $exception->getMessage());
            fwrite($this->streamErrors, "$this->problemRec -- ".print_r($rec, true));
        }
    }
}
