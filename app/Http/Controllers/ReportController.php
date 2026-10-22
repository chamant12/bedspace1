<?php

namespace App\Http\Controllers;

use App\Services\IncomeReports;
use App\Support\BusinessDate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Monthly income reports (FR-12): admins see every hotel and the platform
 * commission; owners see only their own properties.
 */
class ReportController extends Controller
{
    public function __construct(private IncomeReports $reports)
    {
    }

    public function adminIncome(Request $request){
        return $this->income($request, null, 'reports.adminIncome');
    }

    public function ownerIncome(Request $request){
        return $this->income($request, auth()->id(), 'reports.ownerIncome');
    }

    private function income(Request $request, ?int $ownerId, string $view){
        $month = $this->reports->isValidMonth($request->month) ? $request->month : substr(BusinessDate::today(), 0, 7);
        $rows = $this->reports->byProperty($month, $ownerId);
        $totals = $this->reports->totals($rows);

        if ($request->query('format') === 'csv') {
            return $this->csv($month, $rows, $ownerId === null);
        }

        return view($view, compact('month', 'rows', 'totals'));
    }

    private function csv(string $month, $rows, bool $forAdmin): StreamedResponse
    {
        $columns = $forAdmin
            ? ['property_name' => 'Property', 'owner_name' => 'Owner', 'bookings' => 'Bookings', 'online_lkr' => 'Collected online (LKR)',
               'hotel_lkr' => 'Collected at hotel (LKR)', 'gross_lkr' => 'Gross (LKR)', 'commission_lkr' => 'Commission (LKR)',
               'settlement_lkr' => 'Settlement: platform pays hotel (LKR)']
            : ['property_name' => 'Property', 'bookings' => 'Bookings', 'online_lkr' => 'Paid online (LKR)', 'hotel_lkr' => 'Paid at hotel (LKR)',
               'gross_lkr' => 'Gross income (LKR)', 'commission_lkr' => 'Platform commission (LKR)', 'net_to_hotel_lkr' => 'Net income (LKR)',
               'settlement_lkr' => 'Settlement: platform pays you (LKR)'];

        return response()->streamDownload(function () use ($rows, $columns) {
            $out = fopen('php://output', 'w');
            fputcsv($out, array_values($columns));
            foreach ($rows as $row) {
                // Text starting with = + - @ would run as a spreadsheet formula
                fputcsv($out, array_map(fn ($field) => is_string($row->$field) && preg_match('/^[=+\-@]/', $row->$field)
                    ? "'".$row->$field
                    : $row->$field, array_keys($columns)));
            }
            fclose($out);
        }, "income-$month.csv", ['Content-Type' => 'text/csv']);
    }
}
