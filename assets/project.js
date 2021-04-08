$(document).ready(function () {
    var $hasCron = $('#project_hasCron');
    $hasCron.change(function() {
        var $form = $(this).closest('form'),
            $cronFreq = $('.hasCron-group', $form);
        if($(this).prop('checked')) {
            $cronFreq.removeClass('d-none');
        }
        else {
            $cronFreq.addClass('d-none');
        }
    });

    var $gitRemoteRepository = $('#project_gitRemoteRepository');
    $gitRemoteRepository.blur(function() {
        $.ajax({
            url : '/project/ajax/git-branches',
            type: 'POST',
            data : {gitRemoteRepository: $gitRemoteRepository.val()},
            success: function(data) {
                var $gitBranches = $('#project_gitBranch');
                $gitBranches.empty(); // remove old options
                $.each(data, function(key,value) {
                    $gitBranches.append($("<option></option>")
                        .attr("value", value).text(key));
                });
            }
        });
    });
});