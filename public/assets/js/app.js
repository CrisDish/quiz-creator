document.addEventListener('submit',e=>{const form=e.target;if(form.matches('[data-confirm]')&&!confirm(form.dataset.confirm))e.preventDefault();});
document.addEventListener('change',e=>{if(e.target.id==='handout-file'){const n=e.target.files?.[0]?.name;const out=document.querySelector('#upload-name');if(out)out.textContent=n||'No file selected';}});
