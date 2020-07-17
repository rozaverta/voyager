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
@if($isRemove)
    <div data-field-name="{{ $row->field }}">
        <a href="#" class="voyager-x remove-single-image" style="position:absolute;"></a>
        <img src="@if( !filter_var($dataTypeContent->{$row->field}, FILTER_VALIDATE_URL)){{ Voyager::image( $dataTypeContent->{$row->field} ) }}@else{{ $dataTypeContent->{$row->field} }}@endif"
          data-file-name="{{ $dataTypeContent->{$row->field} }}" data-id="{{ $dataTypeContent->getKey() }}"
          @if($dataRowSlug) data-slug="{{ $dataRowSlug }}" @endif
          style="max-width:200px; height:auto; clear:both; display:block; padding:2px; border:1px solid #ddd; margin-bottom:10px;">
    </div>
@endif
<input @if($row->required == 1 && !isset($dataTypeContent->{$row->field})) required @endif type="file" name="{{ $row->field }}" accept="image/*">
