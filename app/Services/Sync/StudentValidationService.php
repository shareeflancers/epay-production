<?php

namespace App\Services\Sync;

use Illuminate\Support\Facades\Validator;
use App\Models\FeeFundCategory;
use Illuminate\Validation\ValidationException;

class StudentValidationService
{
    /**
     * @var array Cache of valid fee category IDs
     */
    protected array $validCategoryIds;

    public function __construct()
    {
        // Pre-fetch valid category IDs once per instance
        $this->validCategoryIds = FeeFundCategory::pluck('id')->toArray();
    }

    /**
     * Validate the raw student array and parse fee categories.
     *
     * @param array $data Raw student data
     * @return array Validated and formatted data
     * @throws ValidationException If validation fails
     */
    public function validate(array $data): array
    {
        $validator = Validator::make($data, [
            's_id' => 'required|integer|digits_between:1,6',
            's_school_idFk' => 'required|integer|digits_between:1,3',
            's_region_idFk' => 'required|integer|digits_between:1,3',
            'std_form_b' => 'required|integer|digits_between:1,13',
            's_name' => 'required|string|max:255',
            'father_or_guardian_name' => 'required|string|max:255',
            'region_name' => 'required|string|max:255',
            'institution_name' => 'required|string|max:255',
            'educational_level' => 'required|string|max:255',
            'section_name' => 'required|string|max:255',
            'class_name' => 'required|string|max:255',
            'fee_category' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        $region = str_pad($validated['s_region_idFk'], 2, '0', STR_PAD_LEFT);
        $school = str_pad($validated['s_school_idFk'], 3, '0', STR_PAD_LEFT);
        $id     = str_pad($validated['s_id'], 6, '0', STR_PAD_LEFT);

        $validated['consumer_number'] = $region . $school . $id;

        $validated['fee_fund_category_ids'] = $this->parseFeeCategories($validated['fee_category'] ?? null);

        return $validated;
    }

    /**
     * Parse and filter fee categories from the external JSON string.
     *
     * @param string|null $feeCategoryJson
     * @return array|null
     */
    protected function parseFeeCategories(?string $feeCategoryJson): ?array
    {
        if (empty($feeCategoryJson)) {
            return null;
        }

        $decodedCategories = json_decode($feeCategoryJson, true);

        if (!is_array($decodedCategories) || count($decodedCategories) === 0) {
            return null;
        }

        // External logic rule: If category 1 and 4 exist, remove 4
        if (in_array(1, $decodedCategories) && in_array(4, $decodedCategories)) {
            $decodedCategories = array_diff($decodedCategories, [4]);
        }

        $validIds = array_values(array_intersect($decodedCategories, $this->validCategoryIds));

        return count($validIds) > 0 ? $validIds : null;
    }
}
