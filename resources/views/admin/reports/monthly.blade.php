@extends('admin.layout')

@section('title', 'Monthly Reports')

@section('page-styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    {{-- Reuse the Dashboard's styles so this page looks identical (Inter font, white bg, cards, tables, dark mode) --}}
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/admin-dashboard.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/admin-reports.css') }}" />
@endsection

@section('page-scripts')
    <script src="{{ asset('functions/admin-reports.js') }}"></script>
@endsection

@section('content')
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title"><h5 class="m-b-10">Monthly Reports</h5></div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Monthly Reports</li>
            </ul>
        </div>

        <div class="page-header-right ms-auto">
            <form method="GET" action="{{ route('admin.monthly-reports') }}" class="rpt-filters" id="rptFilterForm">
                <select name="month" class="form-select rpt-select" aria-label="Month">
                    <option value="0" @selected($month === 0)>All months</option>
                    @foreach (range(1, 12) as $m)
                        <option value="{{ $m }}" @selected($month === $m)>
                            {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                        </option>
                    @endforeach
                </select>

                <select name="year" class="form-select rpt-select" aria-label="Year">
                    @foreach ($yearOptions as $y)
                        <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
                    @endforeach
                </select>

                <a href="{{ route('admin.monthly-reports.pdf', ['year' => $year, 'month' => $month]) }}"
                    class="btn btn-primary rpt-export-btn">
                    <i class="feather-download"></i>
                    <span>Export PDF</span>
                </a>
            </form>
        </div>
    </div>

    {{-- Ang laman nito ay nasa monthly-content.blade.php para magamit din ng AJAX --}}
    <div class="main-content rpt-page">
        @include('admin.reports.monthly-content')
    </div>
@endsection