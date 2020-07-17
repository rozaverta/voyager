@php

    /**
	 * Define vars for ide
	 *
	 * @var \TCG\Voyager\Views\BreadManager $manager
	 * @var \Illuminate\Database\Eloquent\Model $dataTypeContent
	 * @var \Illuminate\Support\ViewErrorBag $errors
	 * @var \TCG\Voyager\Models\DataType $dataType
	 */
		$edit = !is_null($dataTypeContent->getKey());
		$add  = is_null($dataTypeContent->getKey());
		$tabs = $manager->getTabManager();

@endphp

@extends('voyager::master')

@section('css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .tab-content > div {
            padding-left: 0;
        }
        .form-edit-add > .nav {
            padding-left: 15px;
        }
        .panel-form-groups > .panel-body {
            padding: 20px 0;
        }
        .panel-form-groups > .panel-heading > .panel-actions {
            right: 10px;
        }
        .voyager .panel + .panel.panel-action-bar {
            margin-top: -22px;
        }
    </style>
@stop

@section('page_title', __('voyager::generic.'.($edit ? 'edit' : 'add')).' '.$dataType->getTranslatedAttribute('display_name_singular'))

@section('page_header')
    <h1 class="page-title">
        <i class="{{ $dataType->icon }}"></i>
        {{ __('voyager::generic.'.($edit ? 'edit' : 'add')).' '.$dataType->getTranslatedAttribute('display_name_singular') }}
    </h1>
    @include('voyager::multilingual.language-selector')
@stop

@section('content')
    <div class="page-content edit-add container-fluid">
        <div class="row">

            <!-- form start -->
            <form role="form"
                  class="form-edit-add"
                  action="{{ $edit ? route('voyager.'.$dataType->slug.'.update', $dataTypeContent->getKey()) : route('voyager.'.$dataType->slug.'.store') }}"
                  method="POST" enctype="multipart/form-data">

                <!-- PUT Method if we are editing -->
            @if($edit)
                {{ method_field("PUT") }}
            @endif

            <!-- CSRF TOKEN -->
                {{ csrf_field() }}

                <div class="panel-body hidden">
                    @foreach($manager->getHiddenItems() as $row)
                        @include("voyager::bread.form-field", ['row' => $row, 'dataType' => $dataType, 'dataTypeContent' => $dataTypeContent, 'errors' => $errors, 'edit' => $edit, 'add' => $add, 'hidden' => true])
                    @endforeach
                </div>

                @if($tabs->count() > 1)
                    <ul class="nav nav-pills" id="bread-tab" role="tablist">
                        @foreach($tabs->getTabs() as $n => $tab)
                            @php $name = $tab->getName(); @endphp
                            <li class="nav-item{{ $n === 0 ? ' active' : '' }}">
                                <a class="nav-link{{ $n === 0 ? ' active' : '' }}" href="#tab-{{ $name }}" data-toggle="tab" role="tab" aria-controls="tab-{{ $name }}" aria-selected="{{ $n === 0 ? 'true' : 'false' }}">{{ $tab->getLabel() }}</a>
                            </li>
                        @endforeach
                    </ul>
                    <div class="tab-content" id="bread-tab-content">
                        @endif

                        @if (count($errors) > 0)
                            <div class="col-md-12">
                                <div class="alert alert-danger">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        @foreach($tabs->getTabs() as $n => $tab)
                            <div id="tab-{{ $tab->getName() }}" class="tab-pane @if($tabs->count() > 1) fade @if($n === 0) active in @endif @endif " role="tabpanel" aria-labelledby="{{ $tab->getName() }}-tab">
                                @if($tab->isBaseBoards())
                                    @if($tab->isAside())
                                        @include("voyager::bread.board", array_merge(compact('boards', 'dataType', 'dataTypeContent', 'errors', 'edit', 'add'), ['boards' => $tab->getBaseBoards(), 'size' => 'body', 'actionBar' => true]))
                                        @include("voyager::bread.board", array_merge(compact('boards', 'dataType', 'dataTypeContent', 'errors', 'edit', 'add'), ['boards' => $tab->getAsideBoards(), 'size' => 'aside']))
                                    @else
                                        @include("voyager::bread.board", array_merge(compact('boards', 'dataType', 'dataTypeContent', 'errors', 'edit', 'add'), ['boards' => $tab->getBaseBoards(), 'actionBar' => true]))
                                    @endif
                                @elseif($tab->isAside())
                                    @include("voyager::bread.board", array_merge(compact('boards', 'dataType', 'dataTypeContent', 'errors', 'edit', 'add'), ['boards' => $tab->getAsideBoards()]))
                                @endif
                            </div>
                        @endforeach

                        @if($tabs->count() > 1)
                    </div>
                @endif

            </form>

            <iframe id="form_target" name="form_target" style="display:none"></iframe>
            <form id="my_form" action="{{ route('voyager.upload') }}" target="form_target" method="post" enctype="multipart/form-data" style="width:0;height:0;overflow:hidden">
                <input name="image" id="upload_file" type="file" onchange="$('#my_form').submit();this.value='';">
                <input type="hidden" name="type_slug" id="type_slug" value="{{ $dataType->slug }}">
                {{ csrf_field() }}
            </form>

        </div>
    </div>

    <div class="modal fade modal-danger" id="confirm_delete_modal">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"
                            aria-hidden="true">&times;</button>
                    <h4 class="modal-title"><i class="voyager-warning"></i> {{ __('voyager::generic.are_you_sure') }}</h4>
                </div>

                <div class="modal-body">
                    <h4>{{ __('voyager::generic.are_you_sure_delete') }} '<span class="confirm_delete_name"></span>'</h4>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                    <button type="button" class="btn btn-danger" id="confirm_delete">{{ __('voyager::generic.delete_confirm') }}</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete File Modal -->
@stop

@section('javascript')
    <script>
        var params = {};
        var $file;

        function deleteHandler(tag, isMulti) {
            return function() {
                $file = $(this).siblings(tag);

                params = {
                    slug: $file.data('slug') || '{{ $dataType->slug }}',
                    filename:  $file.data('file-name'),
                    id:     $file.data('id'),
                    field:  $file.parent().data('field-name'),
                    multi: isMulti,
                    _token: '{{ csrf_token() }}'
                };

                $('.confirm_delete_name').text(params.filename);
                $('#confirm_delete_modal').modal('show');
            };
        }

        $('document').ready(function () {
            $('.toggleswitch').bootstrapToggle();

            //Init datepicker for date fields if data-datepicker attribute defined
            //or if browser does not handle date inputs
            $('.form-group input[type=date]').each(function (idx, elt) {
                if (elt.hasAttribute('data-datepicker')) {
                    elt.type = 'text';
                    $(elt).datetimepicker($(elt).data('datepicker'));
                } else if (elt.type != 'date') {
                    elt.type = 'text';
                    $(elt).datetimepicker({
                        format: 'L',
                        extraFormats: [ 'YYYY-MM-DD' ]
                    }).datetimepicker($(elt).data('datepicker'));
                }
            });

            @if ($isModelTranslatable)
            $('.side-body').multilingual({"editing": true});
            @endif

            $('.side-body input[data-slug-origin]').each(function(i, el) {
                $(el).slugify();
            });

            $('.form-group').on('click', '.remove-multi-image', deleteHandler('img', true));
            $('.form-group').on('click', '.remove-single-image', deleteHandler('img', false));
            $('.form-group').on('click', '.remove-multi-file', deleteHandler('a', true));
            $('.form-group').on('click', '.remove-single-file', deleteHandler('a', false));

            $('#confirm_delete').on('click', function(){
                $.post('{{ route('voyager.'.$dataType->slug.'.media.remove') }}', params, function (response) {
                    if ( response
                            && response.data
                            && response.data.status
                            && response.data.status == 200 ) {

                        toastr.success(response.data.message);
                        $file.parent().fadeOut(300, function() { $(this).remove(); })
                    } else {
                        toastr.error("Error removing file.");
                    }
                });

                $('#confirm_delete_modal').modal('hide');
            });
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
@stop
