/**
 * Retreat Edit Page JavaScript
 * 
 * Handles retreat editing functionality including permissions and invitations management.
 * Requires jQuery and expects dfxprlRetreatEdit object to be localized.
 * 
 * @package DFXPRL
 * @since 25.12.10
 */

(function($) {
'use strict';

$(document).ready(function() {
var nonce = dfxprlRetreatEdit.nonce;
var retreatId = dfxprlRetreatEdit.retreatId;
var searchTimeout;

// Tab switching
$('.nav-tab').on('click', function(e) {
e.preventDefault();
var tabId = $(this).data('tab');

$('.nav-tab').removeClass('nav-tab-active');
$(this).addClass('nav-tab-active');

$('.tab-content').removeClass('active');
$('#' + tabId).addClass('active');
});

// User search
$('#user-search').on('input', function() {
var searchTerm = $(this).val().trim();

clearTimeout(searchTimeout);

if (searchTerm.length < 2) {
$('#user-search-results').hide();
return;
}

searchTimeout = setTimeout(function() {
searchUsers(searchTerm);
}, 300);
});

// Hide search results when clicking outside
$(document).on('click', function(e) {
if (!$(e.target).closest('.dfxprl-user-search').length) {
$('#user-search-results').hide();
}
});

function searchUsers(searchTerm) {
$.ajax({
url: ajaxurl,
type: 'POST',
data: {
action: 'dfxprl_search_users',
nonce: nonce,
retreat_id: retreatId,
search: searchTerm
},
success: function(response) {
if (response.success) {
displaySearchResults(response.data);
} else {
$('#user-search-results').html('<div class="dfxprl-search-error">' + dfxprlRetreatEdit.i18n.searchFailed + '</div>').show();
}
},
error: function() {
$('#user-search-results').html('<div class="dfxprl-search-error">' + dfxprlRetreatEdit.i18n.searchFailed + '</div>').show();
}
});
}

function displaySearchResults(users) {
var results = $('#user-search-results');
results.empty();

if (users.length === 0) {
results.html('<div class="dfxprl-no-results">' + dfxprlRetreatEdit.i18n.noUsersFound + '</div>');
} else {
var html = '<div class="dfxprl-search-results-list">';
users.forEach(function(user) {
html += '<div class="dfxprl-search-result-item" data-user-id="' + user.id + '">' +
'<strong>' + user.display_name + '</strong> (' + user.user_login + ')' +
'<select class="dfxprl-role-select" data-user-id="' + user.id + '">' +
'<option value="">' + dfxprlRetreatEdit.i18n.selectRole + '</option>' +
'<option value="manager">' + dfxprlRetreatEdit.i18n.retreatManager + '</option>' +
'<option value="message_manager">' + dfxprlRetreatEdit.i18n.messageManager + '</option>' +
'</select>' +
'<button class="button button-small dfxprl-grant-btn" data-user-id="' + user.id + '">' + dfxprlRetreatEdit.i18n.grant + '</button>' +
'</div>';
});
html += '</div>';
results.html(html);
}

results.show();
}

// Grant permission
$(document).on('click', '.dfxprl-grant-btn', function() {
var userId = $(this).data('user-id');
var role = $('.dfxprl-role-select[data-user-id="' + userId + '"]').val();

if (!role) {
alert(dfxprlRetreatEdit.i18n.pleaseSelectRole);
return;
}

grantPermission(userId, role);
});

function grantPermission(userId, role) {
$.ajax({
url: ajaxurl,
type: 'POST',
data: {
action: 'dfxprl_grant_permission',
nonce: nonce,
retreat_id: retreatId,
user_id: userId,
role: role
},
success: function(response) {
if (response.success) {
location.reload();
} else {
alert(dfxprlRetreatEdit.i18n.failedToGrantPermission);
}
},
error: function() {
alert(dfxprlRetreatEdit.i18n.failedToGrantPermission);
}
});
}

// Revoke permission
$(document).on('click', '.dfxprl-revoke-permission', function(e) {
e.preventDefault();
var permissionId = $(this).data('permission-id');

if (confirm($(this).data('confirm'))) {
revokePermission(permissionId);
}
});

function revokePermission(permissionId) {
$.ajax({
url: ajaxurl,
type: 'POST',
data: {
action: 'dfxprl_revoke_permission',
nonce: nonce,
retreat_id: retreatId,
permission_id: permissionId
},
success: function(response) {
if (response.success) {
location.reload();
} else {
alert(dfxprlRetreatEdit.i18n.failedToRevokePermission);
}
},
error: function() {
alert(dfxprlRetreatEdit.i18n.failedToRevokePermission);
}
});
}

// Send invitation
$('#dfxprl-send-invitation-form').on('submit', function(e) {
e.preventDefault();

var email = $('#invitation-email').val();
var role = $('#invitation-role').val();
var firstName = $('#invitation-first-name').val();
var lastName = $('#invitation-last-name').val();

sendInvitation(email, role, firstName, lastName);
});

function sendInvitation(email, role, firstName, lastName) {
$.ajax({
url: ajaxurl,
type: 'POST',
data: {
action: 'dfxprl_send_invitation',
nonce: nonce,
retreat_id: retreatId,
email: email,
role: role,
first_name: firstName,
last_name: lastName
},
success: function(response) {
if (response.success) {
location.reload();
} else {
alert(response.data.message || dfxprlRetreatEdit.i18n.failedToSendInvitation);
}
},
error: function() {
alert(dfxprlRetreatEdit.i18n.failedToSendInvitation);
}
});
}

// Cancel invitation
$(document).on('click', '.dfxprl-cancel-invitation', function(e) {
e.preventDefault();
var invitationId = $(this).data('invitation-id');

if (confirm($(this).data('confirm'))) {
cancelInvitation(invitationId);
}
});

function cancelInvitation(invitationId) {
$.ajax({
url: ajaxurl,
type: 'POST',
data: {
action: 'dfxprl_cancel_invitation',
nonce: nonce,
invitation_id: invitationId
},
success: function(response) {
if (response.success) {
location.reload();
} else {
alert(dfxprlRetreatEdit.i18n.failedToCancelInvitation);
}
},
error: function() {
alert(dfxprlRetreatEdit.i18n.failedToCancelInvitation);
}
});
}
});

})(jQuery);
