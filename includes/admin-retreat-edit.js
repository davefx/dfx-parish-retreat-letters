/**
 * Retreat Edit Page JavaScript
 * 
 * Handles retreat editing functionality including permissions and invitations management.
 * Requires jQuery and expects dfxPRLRetreatEdit object to be localized.
 * 
 * @package DFX_Parish_Retreat_Letters
 * @since 25.12.10
 */

(function($) {
'use strict';

$(document).ready(function() {
var nonce = dfxPRLRetreatEdit.nonce;
var retreatId = dfxPRLRetreatEdit.retreatId;
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
if (!$(e.target).closest('.dfx-prl-user-search').length) {
$('#user-search-results').hide();
}
});

function searchUsers(searchTerm) {
$.ajax({
url: ajaxurl,
type: 'POST',
data: {
action: 'dfx_prl_search_users',
nonce: nonce,
retreat_id: retreatId,
search: searchTerm
},
success: function(response) {
if (response.success) {
displaySearchResults(response.data);
} else {
$('#user-search-results').html('<div class="dfx-prl-search-error">' + dfxPRLRetreatEdit.i18n.searchFailed + '</div>').show();
}
},
error: function() {
$('#user-search-results').html('<div class="dfx-prl-search-error">' + dfxPRLRetreatEdit.i18n.searchFailed + '</div>').show();
}
});
}

function displaySearchResults(users) {
var results = $('#user-search-results');
results.empty();

if (users.length === 0) {
results.html('<div class="dfx-prl-no-results">' + dfxPRLRetreatEdit.i18n.noUsersFound + '</div>');
} else {
var html = '<div class="dfx-prl-search-results-list">';
users.forEach(function(user) {
html += '<div class="dfx-prl-search-result-item" data-user-id="' + user.id + '">' +
'<strong>' + user.display_name + '</strong> (' + user.user_login + ')' +
'<select class="dfx-prl-role-select" data-user-id="' + user.id + '">' +
'<option value="">' + dfxPRLRetreatEdit.i18n.selectRole + '</option>' +
'<option value="manager">' + dfxPRLRetreatEdit.i18n.retreatManager + '</option>' +
'<option value="message_manager">' + dfxPRLRetreatEdit.i18n.messageManager + '</option>' +
'</select>' +
'<button class="button button-small dfx-prl-grant-btn" data-user-id="' + user.id + '">' + dfxPRLRetreatEdit.i18n.grant + '</button>' +
'</div>';
});
html += '</div>';
results.html(html);
}

results.show();
}

// Grant permission
$(document).on('click', '.dfx-prl-grant-btn', function() {
var userId = $(this).data('user-id');
var role = $('.dfx-prl-role-select[data-user-id="' + userId + '"]').val();

if (!role) {
alert(dfxPRLRetreatEdit.i18n.pleaseSelectRole);
return;
}

grantPermission(userId, role);
});

function grantPermission(userId, role) {
$.ajax({
url: ajaxurl,
type: 'POST',
data: {
action: 'dfx_prl_grant_permission',
nonce: nonce,
retreat_id: retreatId,
user_id: userId,
role: role
},
success: function(response) {
if (response.success) {
location.reload();
} else {
alert(dfxPRLRetreatEdit.i18n.failedToGrantPermission);
}
},
error: function() {
alert(dfxPRLRetreatEdit.i18n.failedToGrantPermission);
}
});
}

// Revoke permission
$(document).on('click', '.dfx-prl-revoke-permission', function(e) {
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
action: 'dfx_prl_revoke_permission',
nonce: nonce,
retreat_id: retreatId,
permission_id: permissionId
},
success: function(response) {
if (response.success) {
location.reload();
} else {
alert(dfxPRLRetreatEdit.i18n.failedToRevokePermission);
}
},
error: function() {
alert(dfxPRLRetreatEdit.i18n.failedToRevokePermission);
}
});
}

// Send invitation
$('#dfx-prl-send-invitation-form').on('submit', function(e) {
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
action: 'dfx_prl_send_invitation',
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
alert(response.data.message || dfxPRLRetreatEdit.i18n.failedToSendInvitation);
}
},
error: function() {
alert(dfxPRLRetreatEdit.i18n.failedToSendInvitation);
}
});
}

// Cancel invitation
$(document).on('click', '.dfx-prl-cancel-invitation', function(e) {
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
action: 'dfx_prl_cancel_invitation',
nonce: nonce,
invitation_id: invitationId
},
success: function(response) {
if (response.success) {
location.reload();
} else {
alert(dfxPRLRetreatEdit.i18n.failedToCancelInvitation);
}
},
error: function() {
alert(dfxPRLRetreatEdit.i18n.failedToCancelInvitation);
}
});
}
});

})(jQuery);
