function findPos(obj)
{
	var curtop = 0;
	if (obj.offsetParent) 
	{
		do 
		{
			curtop += obj.offsetTop;
		} while (obj = obj.offsetParent);
		return [curtop - 115];
	}
}

$(document).ready(function(){
		$("#save_and_submit").click(function(){
				check_values = true;
		});
		
		$("#save").click(function(){
				check_values = false;
		});
		
		$("#formulaire").submit(function(){
				if(jQuery.trim($("#nom").val()) == '')
				{
					alert("Vous devez entrer un nom pour votre personnalité !");
					$("#nom").focus();
					window.scroll(0, findPos($("#nom")));
					return false;
				}
				
				var valide = true;
				
				$('.declencheur').each(function(i){
						if($(this).val().charAt(0) != '!')
						{
							alert('Les déclencheurs doivent forcément commencer par un point d\'exclamation.');
							$(this).focus();
							window.scroll(0, findPos(this));
							valide = false;
							return false;
						}
						
						var regexTrigger = new RegExp("![a-z]+$");
						if(regexTrigger.test($(this).val()) == false)
						{
							alert('Les déclencheurs ne doivent contenir que des lettres minuscules (pas de caractères spéciaux, pas d\'accents, pas d\'espace...).');
							$(this).focus();
							window.scroll(0, findPos(this));
							valide = false;
							return false;
						}
				});
				
				$('.phrase').each(function(i){
						
						if(jQuery.trim($(this).val()) != '')
						{
							var nbVariables = $(this).attr('variables');
							var replique = $(this).val();
							
							for(i = 1; i <= nbVariables; i++)
							{
								if(replique.indexOf('$' + i) == -1)
								{
									alert('Il manque une ou plusieurs variables dans une des répliques. N\'oubliez pas que toutes les variables requises doivent apparaitre.');
									$(this).focus();
									window.scroll(0, findPos(this));
									valide = false;
									return false;
								}
							}	
						}
				});
				
				if(valide == false)
					return false;
				
				if(valide == true && check_values == true)
				{
					$('.repliques').each(function(i){
							var allEmpty = true;
							$(this).children('input[type="text"]').each(function(i){
									if(jQuery.trim($(this).val()) != '')
										allEmpty = false;
							});
							if(allEmpty == true)
							{
								alert("Vous n'avez pas rempli une ou plusieurs répliques ! Vous devez toutes les remplir avant de pouvoir faire valider votre personnalité.");
								window.scroll(0, findPos(this));
								valide = false;
								return false;
							}
					});
					if (valide == false)
						return false;
					return confirm("Une fois la demande de validation envoyée, vous ne pourrez plus modifier la personnalité tant qu'elle n'aura pas été acceptée ou refusée. Confirmez-vous votre demande de validation ?");
				}
		});
		
		$(".add_line").click(function(){
				var previous = $(this).prev().prev();
				var regexName = new RegExp("(.+)_([0-9]+)");
				var nameCut = regexName.exec(previous.attr('name'));
				var variables = previous.attr('variables');
				var newLine = document.createElement('input');
				$(newLine).addClass('phrase');
				$(newLine).attr('type', 'text');
				$(newLine).val('');
				$(newLine).attr('variables', variables);
				$(newLine).attr('spellcheck', 'true');
				$(newLine).attr('name', nameCut[1] + '_' + (parseInt(nameCut[2]) + 1));
				$(previous).after(newLine);
		});
});
