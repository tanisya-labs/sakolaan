document.addEventListener('change',function(event){
	if(event.target.matches('.sakolaan-institution-context select')){
		try{window.localStorage.setItem('sakolaanAdmissionInstitution',event.target.value);}catch(error){}
	}
});
document.addEventListener('click',function(event){
	var button=event.target.closest('[data-confirm]');
	if(button&&!window.confirm(button.dataset.confirm)){
		event.preventDefault();
	}
});
