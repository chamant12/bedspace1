<?php

namespace App\Http\Controllers;

use App\Services\IncomeReports;
use App\Services\OperationsReports;
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

    // Occupancy and bookings: admins see all properties, owners their own
    public function occupancy(Request $request, OperationsReports $reports){
        $month = $this->month($request);
        $rows = $reports->occupancy($month, $request->user()->accessiblePropertyIds());
        if ($request->query('format') === 'csv') {
            return $this->simpleCsv("occupancy-$month.csv", $rows, [
                'property_name' => 'Property', 'room_type' => 'Room type', 'rooms' => 'Rooms', 'available_nights' => 'Available room-nights',
                'sold_nights' => 'Sold room-nights', 'occupancy' => 'Occupancy %', 'revenue_lkr' => 'Room revenue (LKR)',
                'adr_lkr' => 'ADR (LKR)', 'revpar_lkr' => 'RevPAR (LKR)', 'revenue_usd' => 'Room revenue (USD)',
                'adr_usd' => 'ADR (USD)', 'revpar_usd' => 'RevPAR (USD)',
            ]);
        }
        return view('reports.occupancy', compact('month', 'rows'));
    }

    public function bookings(Request $request, OperationsReports $reports){
        $month = $this->month($request);
        $rows = $reports->bookings($month, $request->user()->accessiblePropertyIds());
        if ($request->query('format') === 'csv') {
            return $this->simpleCsv("bookings-$month.csv", $rows, [
                'property_name' => 'Property', 'bookings' => 'Bookings', 'confirmed' => 'Confirmed / stayed', 'cancelled' => 'Cancelled / expired',
                'no_show' => 'No-show', 'cancellation_rate' => 'Cancellation %', 'web' => 'Website', 'walk_in' => 'Entered by hotel',
                'online_payment' => 'Paid online', 'pay_at_hotel' => 'Pay at hotel', 'value_lkr' => 'Value (LKR)', 'value_usd' => 'Value (USD)', 'room_nights' => 'Room-nights',
                'avg_nights' => 'Avg nights', 'avg_lead_days' => 'Avg days booked ahead', 'new_guests' => 'New guests', 'returning_guests' => 'Returning guests',
            ]);
        }
        return view('reports.bookings', compact('month', 'rows'));
    }

    private function month(Request $request): string
    {
        return $this->reports->isValidMonth($request->month) ? $request->month : substr(BusinessDate::today(), 0, 7);
    }

    private function simpleCsv(string $fileName, $rows, array $columns): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows, $columns) {
            $out = fopen('php://output', 'w');
            fputcsv($out, array_values($columns));
            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($field) => is_string($row->$field) && preg_match('/^[=+\-@]/', $row->$field)
                    ? "'".$row->$field
                    : $row->$field, array_keys($columns)));
            }
            fclose($out);
        }, $fileName, ['Content-Type' => 'text/csv']);
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
        // One row per property and currency; amounts are in the row's currency
        $columns = $forAdmin
            ? ['property_name' => 'Property', 'owner_name' => 'Owner', 'currency' => 'Currency', 'bookings' => 'Bookings', 'online' => 'Collected online',
               'hotel' => 'Collected at hotel', 'refunds' => 'Refunds', 'gross' => 'Gross after refunds', 'commission' => 'Commission',
               'settlement' => 'Settlement: platform pays hotel']
            : ['property_name' => 'Property', 'currency' => 'Currency', 'bookings' => 'Bookings', 'online' => 'Paid online', 'hotel' => 'Paid at hotel',
               'refunds' => 'Refunds', 'gross' => 'Gross income after refunds', 'commission' => 'Platform commission', 'net_to_hotel' => 'Net income',
               'settlement' => 'Settlement: platform pays you'];

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
