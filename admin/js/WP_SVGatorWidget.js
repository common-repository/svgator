jQuery(function(){
    (function( $ ) {
        'use strict';
        function updateAttachment($control, attachment){
            $control.removeClass('empty');

            if (attachment.responsive) {
                $control.removeClass('has-dimension');
                $control.find('.responsive').prop('checked', true);
            } else {
                $control.addClass('has-dimension');
            }

            $control.find('input.attachment_id').val(attachment.id).trigger('change');
            $control.find('object').prop('data', attachment.icon);
        }

        function openMedia(ev){
            let $widget = $(ev.target).parents('.svgator-widget-control:eq(0)');
            let media = new SVGatorMedia({
                onSelect: function(attachment){
                    updateAttachment($widget, attachment);
                }
            });
            media.open();
        }

        $(function (){
            $("body").on('click', '.select-svgator-media', openMedia);
        });
    })( jQuery );
});