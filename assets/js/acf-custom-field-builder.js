

(function ($) {
    function bindEvents(context) {
        context.find('.select-image').off('click').on('click', function (e) {
            e.preventDefault();
            const button = $(this);
            const container = button.closest('.image-container');
            const input = container.find('.image-id');
            const preview = container.find('.image-preview');

            const frame = wp.media({
                title: 'Select Image',
                button: { text: 'Use this image' },
                multiple: false
            });

            frame.on('open', function () {
                frame.state().get('selection').reset();
            });

            frame.on('select', function () {
                const attachment = frame.state().get('selection').first().toJSON();
                input.val(attachment.id);
                preview.attr('src', attachment.url).show();
                container.find('.remove-image').show();
            });

            frame.open();
        });

        context.find('.remove-row').off('click').on('click', function () {
            $cr = $(this).closest('.acf-custom-repeater-row');
            $cr.find('input, textarea, select').prop('disabled', true);
            $cr.remove();

            //  Re-index all remaining rows
            let rows = context.find('.acf-custom-repeater-row');
            rows.each(function (i) {
                console.log('reindex', i);

                $(this).find('[name]').each(function () {
                    let name = $(this).attr('name');

                    // Replace only the [<number>] part
                    name = name.replace(/\[\d+\]/, '[' + i + ']');

                    $(this).attr('name', name);
                });
            });

            if (rows.length === 0) {
                let fieldName = context.data('name'); // repeater field's name
                context.append(
                    '<input type="hidden" name="' + fieldName + '" value="" />'
                );
            }
        });

        context.find('.remove-image').off('click').on('click', function () {
            const container = $(this).closest('.image-container');
            container.find('.image-id').val('');
            container.find('.image-preview').hide();
            $(this).hide();
        });
    }

    function initialize_field($field) {
        bindEvents($field);
    }

    // block editor needs this
    if (typeof acf.add_action !== 'undefined') {
        if(acfCustomFieldBuilder && typeof acfCustomFieldBuilder !== 'object') {
            Object.keys(acfCustomFieldBuilder).forEach(key => {
                acf.add_action('ready_field/type='+replace(acfCustomFieldBuilder[key]['name'], ' ', '_'), initialize_field);
                acf.add_action('append_field/type='+replace(acfCustomFieldBuilder[key]['name'], ' ', '_'), initialize_field);
            })
        }
    }

    $(document).ready(function () {
        
        Object.keys(acfCustomFieldBuilder).forEach(key => {

            
            const grpName = acfCustomFieldBuilder[key]['name'];
            const keyName = 'acf-field-' + grpName.replace(/\s+/g, '-');
            const fieldDefs = acfCustomFieldBuilder[key]['fields'];
            // console.log('key', '.'+keyName + ' .acf-custom-repeater');

            $('.' + keyName + ' .acf-custom-repeater').each(function () {
                // console.log('repeater found');

                const repeater = $(this);
                const name = repeater.data('name');

                bindEvents(repeater);

                repeater.find('.add-repeater-row').off('click').on('click', function () {
                    // console.log('repeater clicked');

                    const index = repeater.find('.acf-custom-repeater-row').length;
                    let rowHtml = '<div class="acf-custom-repeater-row" style="background-color:#eee;padding:1rem;margin-top:1rem;">';

                    fieldDefs.forEach(function (fieldDef) {
                        const label = fieldDef.label;
                        const type = fieldDef.type;
                        const key = label.replace(/\s+/g, '-').toLowerCase();
                        rowHtml += `<div class="acf-field-item" style="margin-bottom:1rem;">`;
                        rowHtml += `<label style="display:block;margin-bottom:5px;">${label}</label>`;

                        if (type === 'textarea') {
                            rowHtml += `<textarea name="${name}[${index}][${key}]" style="width:100%;height:80px;"></textarea>`;
                        } else if (type === 'image') {
                            rowHtml += `<div class="image-container" style="display:flex;flex-direction:column;width:fit-content;gap:8px;margin-bottom:1rem;">`;
                            rowHtml += `<input type="hidden" class="image-id" name="${name}[${index}][${key}]" value=""/>`;
                            rowHtml += `<div class="image-preview-actions">`;
                            rowHtml += `<button type="button" class="button select-image">Select Image</button>`;
                            rowHtml += `<button type="button" class="button remove-image" style="display:none;">Remove</button>`;
                            rowHtml += `</div>`;
                            rowHtml += `<img class="image-preview" src="" style="max-width:100px;margin-top:10px;margin-bottom:1rem;display:none;" />`;
                            rowHtml += `</div>`;
                        } else if (type === 'wysiwyg') {
                            const editorId = `${name.replace(/\[|\]/g, '_')}_${index}_${key}`;
                            rowHtml += `<textarea id="${editorId}" name="${name}[${index}][${key}]" style="margin-bottom:1rem;height:100px;"></textarea>`;
                            setTimeout(function () { initWYGEditor(editorId); }, 50);
                        } else {
                            rowHtml += `<input type="text" name="${name}[${index}][${key}]" style="width:100%;" />`;
                        }

                        rowHtml += '</div>'; // .acf-field-item
                    });

                    rowHtml += `<button type="button" class="button remove-row" style="margin-top:1rem;">Remove</button>`;
                    rowHtml += '</div>';

                    const row = $(rowHtml);
                    row.insertBefore($(this));
                    bindEvents(row);
                });
            });
        });
    });

    function initWYGEditor(editorId) {
        var textarea = document.getElementById(editorId);
        if (!textarea) return;

        if (window.tinymce && tinymce.get(editorId)) tinymce.get(editorId).remove();

        if (typeof wp !== 'undefined' && wp.editor && typeof wp.editor.initialize === 'function') {
            try {
                wp.editor.initialize(editorId, {
                    tinymce: {
                        wpautop: true,
                        plugins: 'wordpress lists paste link',
                        toolbar1: 'formatselect bold italic bullist numlist blockquote link unlink undo redo',
                        toolbar2: '',
                    },
                    quicktags: true,
                    mediaButtons: false
                });
                return;
            } catch (err) { }
        }

        setTimeout(function () {
            if (typeof tinymce !== 'undefined') {
                tinymce.init({
                    selector: '#' + editorId,
                    menubar: false,
                    plugins: 'lists paste link',
                    toolbar: 'formatselect bold italic bullist numlist blockquote link unlink undo redo',
                    setup: function (editor) {
                        editor.on('init', function () { editor.setContent(textarea.value || ''); });
                        editor.on('change keyup NodeChange', function () { editor.save(); });
                    }
                });
            }
        }, 120);
    }
})(jQuery);
