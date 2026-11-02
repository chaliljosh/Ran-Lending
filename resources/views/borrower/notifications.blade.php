@extends('borrower.layout')

@section('title', 'Notifications')

@section('content')
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Notifications</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('borrower.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Notifications</li>
            </ul>
        </div>
    </div>

    <div class="main-content">
        @php
            $nfAll = $headerNotifications ?? collect();
        @endphp

        {{-- data-nf-page-size = ilang notifications kada page --}}
        <div class="bwl-nf-page" data-nf-root data-nf-page-size="10">

            {{-- Loader: lalabas kapag nag-click ng Mark all as read, tabs, chips, o pagination --}}
            <div class="bwl-nf-loader" data-nf-loader hidden>
                <span class="bwl-nf-spinner" role="status" aria-label="Loading"></span>
            </div>

            <div class="bwl-nf-head">
                <h6 class="bwl-nf-heading">All notifications</h6>
                <button type="button" class="bwl-nf-markall" data-nf-markall
                    data-url="{{ route('borrower.notifications.mark-all-read') }}"
                    @if (($unreadNotifCount ?? 0) < 1) hidden @endif>Mark all as read</button>
            </div>

            <div class="bwl-nf-toolbar">
                <div class="bwl-nf-tabs" role="tablist">
                    <button type="button" class="bwl-nf-tab is-active" data-nf-tab="all" role="tab"
                        aria-selected="true">All</button>
                    <button type="button" class="bwl-nf-tab" data-nf-tab="unread" role="tab"
                        aria-selected="false">Unread</button>
                </div>

                <div class="bwl-nf-chips" role="group" aria-label="Filter by type">
                    <button type="button" class="bwl-nf-chip is-active" data-nf-cat="all">All types</button>
                    <button type="button" class="bwl-nf-chip" data-nf-cat="loans">Loans</button>
                    <button type="button" class="bwl-nf-chip" data-nf-cat="payments">Payments</button>
                    <button type="button" class="bwl-nf-chip" data-nf-cat="account">Account</button>
                </div>
            </div>

            <div class="bwl-nf-list">
                @foreach ($nfAll as $n)
                    @include('borrower.partials.notification-item', ['n' => $n])
                @endforeach
                <p class="bwl-nf-empty" data-nf-empty @if ($nfAll->isNotEmpty()) hidden @endif>
                    No notifications yet.</p>
            </div>

            <div class="bwl-nf-pager" data-nf-pager hidden>
                <span class="bwl-nf-pager-info" data-nf-pager-info></span>
                <nav class="bwl-nf-pager-nav" data-nf-pager-nav aria-label="Pagination"></nav>
            </div>
        </div>
    </div>
@endsection