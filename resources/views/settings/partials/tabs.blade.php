{{-- Settings navigation: which settings page, which lead type and (for Documents) which property type.
     Expects $page ('pipeline'|'documents'), $leadType and $propertyType. --}}
@php
    $leadTypeLabels = ['buyer' => __('Buyer'), 'seller' => __('Seller'), 'renter' => __('Renter'), 'landlord' => __('Landlord')];
    $propertyTypeLabels = ['condo' => __('Condo'), 'house' => __('House'), 'land' => __('Land'), 'commercial' => __('Commercial')];
    $pill = fn (bool $on) => 'px-3 py-1 rounded-full text-[12.1px] '.($on ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200');
@endphp

<div class="flex items-center gap-6 border-b border-gray-200 pb-3 mb-4 text-[12.1px]">
    <a href="{{ route('settings.pipeline', ['type' => $leadType, 'property_type' => $propertyType]) }}" class="{{ $page === 'pipeline' ? 'font-bold text-gray-900' : 'text-gray-600 hover:text-gray-900' }}">{{ __('Pipeline columns') }}</a>
    <a href="{{ route('settings.documents', ['type' => $leadType, 'property_type' => $propertyType]) }}" class="{{ $page === 'documents' ? 'font-bold text-gray-900' : 'text-gray-600 hover:text-gray-900' }}">{{ __('Documents') }}</a>
</div>

<div class="flex flex-wrap items-center gap-2 mb-3">
    <span class="text-[12.1px] text-gray-400 mr-1">{{ __('Lead type') }}</span>
    @foreach ($leadTypeLabels as $value => $label)
        <a href="{{ route('settings.'.$page, ['type' => $value, 'property_type' => $propertyType]) }}" class="{{ $pill($leadType === $value) }}">{{ $label }}</a>
    @endforeach
</div>

<div class="flex flex-wrap items-center gap-2 mb-4">
    <span class="text-[12.1px] text-gray-400 mr-1">{{ __('Property type') }}</span>
    @foreach ($propertyTypeLabels as $value => $label)
        <a href="{{ route('settings.'.$page, ['type' => $leadType, 'property_type' => $value]) }}" class="{{ $pill($propertyType === $value) }}">{{ $label }}</a>
    @endforeach
</div>
