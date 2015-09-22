(function ($) {
    var activeElement = null;
    
    $(document).ready(function() {
        $('tr.element').click(function(e) {
            if (activeElement !== null) {
                activeElement.removeClass('active');
            }
            
            activeElement = $(e.target).closest('tr.element');
            activeElement.addClass('active');
        });
        
        $('li.selector-child').on('click', function(e){
            e.stopPropagation();
            console.log(activeElement);
            //looks like a stopPropagation on the selector-parent forces
            //me to bind the event lower down the DOM, then work back
            //up to the li
            var targetLi = $(e.target).closest('li.selector-child');
            if (activeElement == null) {
                alert("Select an element at the left before choosing a property.");
            } else {
                activeElement.find('td.mapping').append('<p>' + targetLi.data('child-search') + '</p>');
            }
        });
        
    });
    
    
})(jQuery);