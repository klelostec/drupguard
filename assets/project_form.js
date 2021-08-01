$(document).ready(function() {
    /* Machine name helper */
    var $machineNameInput = $('#project_machineName'),
        $nameInput = $('#project_name'),
        $machineNameRow = $('.js-machine-name-row');
    if ($machineNameInput.length) {
        var valMachineName = $machineNameInput.val(),
            valName = $nameInput.val(),
            machineNameShown = false;
        if (valMachineName && valName) {
            $.get($nameInput.data('ajax-machine-name'), {
                name: valName,
            }).done(function (data) {
                if (data !== valMachineName) {
                    $machineNameRow.removeClass('d-none');
                    machineNameShown = true;
                }
            });
        }

        if (!machineNameShown) {
            $('<p id="project_name_help" class="form-text mb-0 help-text d-none">Machine name : <span></span> <a href="#">Change</a></p>').insertAfter($nameInput);
            var $nameHelp = $('#project_name_help');
            $('a', $nameHelp).click(function (e) {
                e.preventDefault();
                machineNameShown = true;
                $machineNameRow.removeClass('d-none');
                $nameHelp.addClass('d-none');
                $nameInput.off('change.machineName input.machineName');
            });
            $nameInput.on('change.machineName input.machineName', function (e) {
                if($(this).val() === '') {
                    $nameHelp.addClass('d-none');
                }
                else {
                    $.get($nameInput.data('ajax-machine-name'), {
                        name: $nameInput.val(),
                    }).done(function (data) {
                        $machineNameInput.val(data);
                        $('span', $nameHelp).text(data);
                        $nameHelp.removeClass('d-none');
                    });
                }
            });
        }
    }

    /* Git management */
    var $gitRemoteInput = $('.js-git-remote-repository');
    var $gitBranchTarget = $('.js-git-branch-target');

    var initGitRemote = '';
    $gitRemoteInput.on('focus.git_remote', function() {
        initGitRemote = $(this).val();
    });
    $gitRemoteInput.on('blur.git_remote', function() {
        if($(this).val() === initGitRemote) {
            return;
        }
        $.ajax({
            url: $gitRemoteInput.data('ajax-git-branches'),
            data: {
                repo: $gitRemoteInput.val(),
                branch: $('#project_gitBranch').val()
            }
        }).done(function (data) {
            if (!data || !data.html) {
                $gitBranchTarget.empty();
                $gitBranchTarget.addClass('d-none');
            }
            else {
                // Replace the current field and show
                $gitBranchTarget
                    .html(data.html)
                    .removeClass('d-none')
            }
            if(data.error) {
                showModal('Git error', data.error);
            }
        });
    });

    /* Email */
    $('#project_needEmail').change(function () {
        if ($(this).prop('checked')) {
            $('.js-email-level-row').removeClass('d-none');
            $('.js-email-extra-row').removeClass('d-none');
        } else {
            $('.js-email-level-row').addClass('d-none');
            $('.js-email-extra-row').addClass('d-none');
            $('#project_emailLevel').val(0);
            $('#project_emailExtra').val('');
        }
    });

    /* Cron */
    $('#project_hasCron').change(function () {
        if ($(this).prop('checked')) {
            $('.js-cron-frequency-row').removeClass('d-none');
        } else {
            $('.js-cron-frequency-row').addClass('d-none');
            $('#project_cronFrequency').val('');
        }
    });

    /* Public */
    $('#project_isPublic').change(function () {
        if ($(this).prop('checked')) {
            $('.js-allowed-users-row').addClass('d-none');
            $('.js-allowed-users-row .autocomplete-widget-item').remove('');
        } else {
            $('.js-allowed-users-row').removeClass('d-none');
        }
    });

});