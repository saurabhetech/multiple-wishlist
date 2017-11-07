function toggleReadOnlyEditor(ed) {
               ed.makeReadOnly(!ed.settings.readonly) ? "Disable ReadOnly" : "Enable ReadOnly";
}

function disableEditors(){
	//vars defined in element.phtml
	if(template_user_notification){
		toggleReadOnlyEditor(tinyMCE.get('template_user_notification'));
	}
	if(template_admin_notification){
		toggleReadOnlyEditor(tinyMCE.get('template_admin_notification'));
	}
	if(template_guest_notification){
		toggleReadOnlyEditor(tinyMCE.get('template_guest_notification'));
	}
}

function itoris_toogleFieldEditMode(toogleIdentifier, fieldContainer) {

	if ($(toogleIdentifier).checked) {
		if(fieldContainer == 'template_user_notification'
				|| fieldContainer == 'template_admin_notification'
				|| fieldContainer == 'template_guest_notification'
		){
			toggleReadOnlyEditor(tinyMCE.get(fieldContainer));
		}
		$(fieldContainer).disabled = true;
		if(fieldContainer == 'notify_administrator'){
			$('admin_email').disabled = true;
		}
    } else {
		if(fieldContainer == 'template_user_notification'
				|| fieldContainer == 'template_admin_notification'
				|| fieldContainer == 'template_guest_notification'
		){
			toggleReadOnlyEditor(tinyMCE.get(fieldContainer));
		}
        $(fieldContainer).disabled = false;
		if(fieldContainer == 'notify_administrator'){
			if($(fieldContainer).checked){
				$('admin_email').disabled = false;
			}else{
				$('admin_email').disabled = true;
			}
		}
    }
}