<?php

namespace App\Services;

use App\Models\Consumer;
use App\Models\ActiveChallan;

class ChallanService
{
    /**
     * Get single challan by identification number.
     *
     * @param string $identificationNumber
     * @return ActiveChallan|null
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getSingleChallan(string $identificationNumber): ?ActiveChallan
    {
        $consumer = Consumer::where('identification_number', $identificationNumber)->first();

        if (!$consumer) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Consumer not found');
        }

        return ActiveChallan::where('consumer_id', $consumer->id)
            ->orderBy('due_date', 'desc')
            ->first();
    }

    /**
     * Get bulk print URL for the given IDs.
     *
     * @param array $ids
     * @return string
     */
    public function getBulkChallansPrintUrl(array $ids): string
    {
        return route('challans.bulk-print', ['ids' => implode(',', $ids)]);
    }

    /**
     * Get challan status for list of identification numbers.
     *
     * @param array $identificationNumbers
     * @return array
     */
    public function getChallanStatus(array $identificationNumbers): array
    {
        $consumers = Consumer::whereIn('identification_number', $identificationNumbers)->get(['id', 'identification_number']);
        $consumerMap = $consumers->pluck('identification_number', 'id')->toArray();

        $challans = ActiveChallan::whereIn('consumer_id', array_keys($consumerMap))->get();

        $data = [];
        foreach ($challans as $challan) {
            $idNumber = $consumerMap[$challan->consumer_id] ?? null;
            if ($idNumber) {
                if (!isset($data[$idNumber])) {
                    $data[$idNumber] = [];
                }
                $data[$idNumber][] = [
                    'status' => $challan->status,
                    'amount_within_dueDate' => $challan->amount_within_dueDate,
                    'billing_month' => $challan->reserved ? trim(explode('|', $challan->reserved)[1]) : null,
                ];
            }
        }

        return $data;
    }
}
