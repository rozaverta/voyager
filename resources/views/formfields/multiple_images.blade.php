@php
    /** @var \TCG\Voyager\Models\DataRow $row */
    /** @var \TCG\Voyager\Models\DataType $dataType */
    /** @var \Illuminate\Database\Eloquent\Model $dataTypeContent */

    $dataRowSlug = null;
    $isRemove = isset($dataTypeContent->{$row->field});
    if($isRemove && $dataType->name !== $dataTypeContent->getTable()) {
        $dataRowComponent = \TCG\Voyager\Models\DataType::where("name", $dataTypeContent->getTable())->first();
        if($dataRowComponent) {
            $dataRowSlug = $dataRowComponent->slug;
        }
    }
@endphp
<br>
@if($isRemove)
    @php $images = json_decode($dataTypeContent->{$row->field}); @endphp
    @if($images != null)
        @foreach($images as $image)
            <div class="img_settings_container" data-field-name="{{ $row->field }}" style="float:left;padding-right:15px;">
                <a href="#" class="voyager-x remove-multi-image" style="position: absolute;"></a>
                <img src="{{ Voyager::image( $image ) }}" @if($dataRowSlug) data-slug="{{ $dataRowSlug }}" @endif data-file-name="{{ $image }}" data-id="{{ $dataTypeContent->getKey() }}" style="max-width:200px; height:auto; clear:both; display:block; padding:2px; border:1px solid #ddd; margin-bottom:5px;">
            </div>
        @endforeach
    @endif
@endif
<div class="clearfix"></div>
<input @if($row->required == 1 && !isset($dataTypeContent->{$row->field})) required @endif type="file" name="{{ $row->field }}[]" multiple="multiple" accept="image/*">
