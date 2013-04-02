

function confirm_delete(){
    if (confirm(confirmtext)){
        document.forms['processform'].what.value='deleteall';
        document.forms['processform'].submit();
        return true;
    }
    return false;
}