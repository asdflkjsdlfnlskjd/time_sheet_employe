<?php

namespace App\Exports;

use App\Models\TimeRecord;
use App\Models\Employee;
use Carbon\Carbon;

class TimeRecordExportXlsx
{
    private $currentMonth;
    private $currentYear;
    private $employees;
    private $departmentId;

    public function __construct($month, $year, $departmentId = null, $employees = null)
    {
        $this->currentMonth = $month;
        $this->currentYear = $year;
        $this->departmentId = $departmentId;
        $this->employees = $employees;
    }

    /**
     * Экспортирует в XLSX формат с форматированием
     */
    public function toXlsx()
    {
        $monthNames = [
            1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель',
            5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август',
            9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь'
        ];

        $monthStart = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1);
        $monthEnd = $monthStart->copy()->endOfMonth();
        $daysInMonth = $monthStart->daysInMonth;

        // Получаем TimeRecords
        $employeeIds = $this->employees->pluck('id')->toArray();
        $timeRecords = TimeRecord::whereIn('employee_id', $employeeIds)
            ->whereDate('date', '>=', $monthStart->format('Y-m-d'))
            ->whereDate('date', '<=', $monthEnd->format('Y-m-d'))
            ->get()
            ->groupBy('employee_id')
            ->map(function($records) use ($daysInMonth) {
                $daysData = $records->keyBy(fn($r) => $r->date->day);
                return collect(range(1, $daysInMonth))
                    ->mapWithKeys(fn($day) => [$day => $daysData[$day] ?? null]);
            });

        // Генерируем HTML для открытия в Excel
        $html = $this->generateHtml($monthNames, $daysInMonth, $timeRecords);

        $filename = 'timesheet_' . $this->currentMonth . '_' . $this->currentYear . '_' . now()->format('Y-m-d_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $html;
        exit;
    }

    /**
     * Генерирует HTML таблицу для Excel
     */
    private function generateHtml($monthNames, $daysInMonth, $timeRecords)
    {
        $monthName = $monthNames[$this->currentMonth];
        $statusMap = TimeRecord::getStatusMap();

        $html = <<<HTML
<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
 <DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">
  <Author>TimeSheet System</Author>
  <LastAuthor>TimeSheet System</LastAuthor>
  <Created>{now()}</Created>
  <Company>Company</Company>
  <Version>16.00</Version>
 </DocumentProperties>
 <ExcelWorkbook xmlns="urn:schemas-microsoft-com:office:excel">
  <WindowHeight>7920</WindowHeight>
  <WindowWidth>21570</WindowWidth>
  <WindowTopX>480</WindowTopX>
  <WindowTopY>120</WindowTopY>
  <ProtectStructure>False</ProtectStructure>
  <ProtectWindows>False</ProtectWindows>
 </ExcelWorkbook>
 <Styles>
  <Style ss:ID="Default" ss:Name="Default">
   <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>
   </Borders>
   <Font ss:FontName="Calibri" x:CharSet="204" ss:Size="11"/>
   <Interior ss:Color="#FFFFFF" ss:Pattern="Solid"/>
   <NumberFormat ss:Format="General"/>
  </Style>

  <Style ss:ID="Title" ss:Name="Title">
   <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
   <Font ss:FontName="Calibri" x:CharSet="204" ss:Size="16" ss:Bold="1" ss:Color="#FFFFFF"/>
   <Interior ss:Color="#1F4E78" ss:Pattern="Solid"/>
  </Style>

  <Style ss:ID="Header" ss:Name="Header">
   <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#000000"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#000000"/>
   </Borders>
   <Font ss:FontName="Calibri" x:CharSet="204" ss:Size="11" ss:Bold="1" ss:Color="#FFFFFF"/>
   <Interior ss:Color="#4472C4" ss:Pattern="Solid"/>
   <NumberFormat ss:Format="General"/>
  </Style>

  <Style ss:ID="DayHeader" ss:Name="DayHeader">
   <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>
   </Borders>
   <Font ss:FontName="Calibri" x:CharSet="204" ss:Size="10" ss:Bold="1" ss:Color="#FFFFFF"/>
   <Interior ss:Color="#5B9BD5" ss:Pattern="Solid"/>
   <NumberFormat ss:Format="General"/>
  </Style>

  <Style ss:ID="DataCell" ss:Name="DataCell">
   <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D0D0D0"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D0D0D0"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D0D0D0"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D0D0D0"/>
   </Borders>
   <Font ss:FontName="Calibri" x:CharSet="204" ss:Size="10"/>
   <Interior ss:Color="#F2F2F2" ss:Pattern="Solid"/>
  </Style>

  <Style ss:ID="TotalCell" ss:Name="TotalCell">
   <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>
   </Borders>
   <Font ss:FontName="Calibri" x:CharSet="204" ss:Size="10" ss:Bold="1"/>
   <Interior ss:Color="#E8F0F7" ss:Pattern="Solid"/>
   <NumberFormat ss:Format="General"/>
  </Style>

  <Style ss:ID="InfoCell" ss:Name="InfoCell">
   <Alignment ss:Horizontal="Left" ss:Vertical="Center" ss:WrapText="1"/>
   <Font ss:FontName="Calibri" x:CharSet="204" ss:Size="11"/>
  </Style>
 </Styles>

 <Worksheet ss:Name="Табель">
  <Table ss:ExpandedColumnCount="{$this->getColCount($daysInMonth)}" ss:ExpandedRowCount="{count($this->employees) + 7}" x:FullColumns="1"
   x:FullRows="1" ss:DefaultRowHeight="30" ss:DefaultColumnWidth="50">

HTML;

        // Ширина колонок
        $html .= '<Column ss:Width="150" ss:AutoFitWidth="0"/>';
        $html .= '<Column ss:Width="80" ss:AutoFitWidth="0"/>';
        $html .= '<Column ss:Width="120" ss:AutoFitWidth="0"/>';
        
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $html .= '<Column ss:Width="50" ss:AutoFitWidth="0"/>';
        }
        $html .= '<Column ss:Width="80" ss:AutoFitWidth="0"/>';
        $html .= '<Column ss:Width="80" ss:AutoFitWidth="0"/>';

        // Заголовок
        $html .= '<Row ss:AutoFitHeight="1" ss:Height="35">';
        $html .= '<Cell ss:StyleID="Title" ss:MergedAcross="' . ($this->getColCount($daysInMonth) - 1) . '"><Data ss:Type="String">Табель учета рабочего времени - ' . $monthName . ' ' . $this->currentYear . ' г.</Data></Cell>';
        $html .= '</Row>';

        // Пустая строка
        $html .= '<Row ss:AutoFitHeight="1"><Cell></Cell></Row>';

        // Строка с легендой статусов
        $html .= '<Row ss:AutoFitHeight="1" ss:Height="25">';
        $html .= '<Cell ss:StyleID="InfoCell" ss:MergedAcross="' . ($this->getColCount($daysInMonth) - 1) . '"><Data ss:Type="String">Статусы: ';
        $statusList = [];
        foreach ($statusMap as $key => $info) {
            $statusList[] = $info['short'] . ' = ' . $info['label'];
        }
        $html .= implode(' | ', $statusList);
        $html .= '</Data></Cell>';
        $html .= '</Row>';

        // Пустая строка
        $html .= '<Row ss:AutoFitHeight="1"><Cell></Cell></Row>';

        // Заголовок таблицы
        $html .= '<Row ss:AutoFitHeight="1" ss:Height="25">';
        $html .= '<Cell ss:StyleID="Header"><Data ss:Type="String">Сотрудник</Data></Cell>';
        $html .= '<Cell ss:StyleID="Header"><Data ss:Type="String">Табель №</Data></Cell>';
        $html .= '<Cell ss:StyleID="Header"><Data ss:Type="String">Отдел</Data></Cell>';

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $html .= '<Cell ss:StyleID="DayHeader"><Data ss:Type="String">День ' . $day . '</Data></Cell>';
        }

        $html .= '<Cell ss:StyleID="Header"><Data ss:Type="String">Рабочих дней</Data></Cell>';
        $html .= '<Cell ss:StyleID="Header"><Data ss:Type="String">Часов</Data></Cell>';
        $html .= '</Row>';

        // Данные сотрудников
        foreach ($this->employees as $employee) {
            $html .= '<Row ss:AutoFitHeight="1" ss:Height="20">';
            
            $html .= '<Cell ss:StyleID="DataCell"><Data ss:Type="String">' . htmlspecialchars($employee->last_name . ' ' . $employee->first_name . ' ' . $employee->middle_name) . '</Data></Cell>';
            $html .= '<Cell ss:StyleID="DataCell"><Data ss:Type="String">' . htmlspecialchars($employee->tab_number) . '</Data></Cell>';
            $html .= '<Cell ss:StyleID="DataCell"><Data ss:Type="String">' . htmlspecialchars($employee->department->name ?? 'Без отдела') . '</Data></Cell>';

            $totalDays = 0;
            $totalHours = 0;

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $record = $timeRecords[$employee->id][$day] ?? null;
                $hours = $record ? $record->hours : 0;
                $status = $record ? $record->status : 'present';

                $statusShort = $statusMap[$status]['short'] ?? '—';
                $cellValue = ($hours > 0) ? $statusShort . '(' . $hours . 'ч)' : '-';

                $html .= '<Cell ss:StyleID="DataCell"><Data ss:Type="String">' . htmlspecialchars($cellValue) . '</Data></Cell>';

                $totalHours += $hours;
                if ($hours > 0) {
                    $totalDays += 1;
                }
            }

            $html .= '<Cell ss:StyleID="TotalCell"><Data ss:Type="Number">' . $totalDays . '</Data></Cell>';
            $html .= '<Cell ss:StyleID="TotalCell"><Data ss:Type="Number">' . number_format($totalHours, 1, '.', '') . '</Data></Cell>';
            $html .= '</Row>';
        }

        $html .= '  </Table>';
        $html .= '  <WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">';
        $html .= '   <Print>';
        $html .= '    <ValidPrinterInfo/>';
        $html .= '    <HorizontalResolution>300</HorizontalResolution>';
        $html .= '    <VerticalResolution>300</VerticalResolution>';
        $html .= '   </Print>';
        $html .= '   <Panes>';
        $html .= '    <Pane>';
        $html .= '     <Number>3</Number>';
        $html .= '     <ActivePane>BottomRight</ActivePane>';
        $html .= '     <SplitHorizontal>3</SplitHorizontal>';
        $html .= '     <SplitVertical>3</SplitVertical>';
        $html .= '     <TopRowBottomPane>4</TopRowBottomPane>';
        $html .= '     <LeftColumnRightPane>3</LeftColumnRightPane>';
        $html .= '    </Pane>';
        $html .= '   </Panes>';
        $html .= '   <PageBreakZoom>0</PageBreakZoom>';
        $html .= '   <Selected/>';
        $html .= '   <Zoom>100</Zoom>';
        $html .= '   <TabColorIndex>64</TabColorIndex>';
        $html .= '   <ProtectObjects>False</ProtectObjects>';
        $html .= '   <ProtectScenarios>False</ProtectScenarios>';
        $html .= '  </WorksheetOptions>';
        $html .= ' </Worksheet>';
        $html .= '</Workbook>';

        return $html;
    }

    /**
     * Возвращает количество колонок
     */
    private function getColCount($daysInMonth)
    {
        return 3 + $daysInMonth + 2; // Сотрудник + Табель + Отдел + Дни + Рабочих дней + Часов
    }
}
