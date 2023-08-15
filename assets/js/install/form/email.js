"use strict";

const $ = require('jquery');

$(document).ready(function (e) {
    let email_type = $('select#install_email_type_install');
    let email_dsn_custom=$('input#install_email_dsn_custom');
    let email_command=$('input#install_email_command');
    let email_host=$('input#install_email_host');
    let email_user=$('input#install_email_user');
    let email_password=$('input#install_email_password');
    let email_local_domain=$('input#install_email_local_domain');
    let email_restart_threshold=$('input#install_email_restart_threshold');
    let email_restart_threshold_sleep=$('input#install_email_restart_threshold_sleep');
    let email_ping_threshold=$('input#install_email_ping_threshold');
    let email_max_per_second=$('input#install_email_max_per_second');
    let email_check=$('div#install_email_container');
    let email=$('input#install_email');

    let reset = function() {
        $('.invalid-feedback').remove();
        $('.is-invalid').removeClass('is-invalid');
        email_dsn_custom.val('');
        email_command.val('');
        email_host.val('');
        email_user.val('');
        email_password.val('');
        email_local_domain.val('');
        email_restart_threshold.val('');
        email_restart_threshold_sleep.val('');
        email_ping_threshold.val('');
        email_max_per_second.val('');
        email.val('');
    }

    let change = function (e) {
        if(e !== undefined) {
            reset();
        }
        switch (email_type.val()) {
            case 'custom':
                email_command.parent().hide();
                email_dsn_custom.parent().show();
                email_host.parent().hide();
                email_host.attr('required', false);
                email_user.parent().hide();
                email_password.parent().hide();
                email_local_domain.parent().hide();
                email_restart_threshold.parent().hide();
                email_restart_threshold_sleep.parent().hide();
                email_ping_threshold.parent().hide();
                email_max_per_second.parent().hide();
                email_check.show();
                break;
            case 'sendmail':
                email_command.parent().show();
                email_dsn_custom.parent().hide();
                email_host.parent().hide();
                email_host.attr('required', false);
                email_user.parent().hide();
                email_password.parent().hide();
                email_local_domain.parent().hide();
                email_restart_threshold.parent().hide();
                email_restart_threshold_sleep.parent().hide();
                email_ping_threshold.parent().hide();
                email_max_per_second.parent().hide();
                email_check.show();
                break;
            case 'smtp':
            case 'smtps':
                email_dsn_custom.parent().hide();
                email_command.parent().hide();
                email_host.parent().show();
                email_host.attr('required', 'required');
                email_user.parent().show();
                email_password.parent().show();
                email_local_domain.parent().show();
                email_restart_threshold.parent().show();
                email_restart_threshold_sleep.parent().show();
                email_ping_threshold.parent().show();
                email_max_per_second.parent().show();
                email_check.show();
                break;
            case 'native':
            default:
                email_command.parent().hide();
                email_dsn_custom.parent().hide();
                email_host.parent().hide();
                email_host.attr('required', false);
                email_user.parent().hide();
                email_password.parent().hide();
                email_local_domain.parent().hide();
                email_restart_threshold.parent().hide();
                email_restart_threshold_sleep.parent().hide();
                email_ping_threshold.parent().hide();
                email_max_per_second.parent().hide();
                if (email_type.val() === 'native') {
                    email_check.show();
                }
                else {
                    email_check.hide();
                }
                break;
        }
    };
    email_type.change(change)
    change();
});
