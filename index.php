<script language="javascript" type="text/javascript">
   
    function Solo_Numerico(variable){
        Numer=parseInt(variable);
        if (isNaN(Numer)){
         alert("No has introducido un numero");
            return "";
        }
        return Numer;
    }
    function ValNumero(Control){
        Control.value=Solo_Numerico(Control.value);
    }
</script>
<form name="form_date" method="get" action="index_cron.php">
 <fieldset>
  <legend align="left">Initial Date</legend>
  Day: <input type="text" size="2" maxlength="2" name="idate_day" onChange="return ValNumero(this);" />
  <br /><br />
  Month: <input type="text" size="2" maxlength="2" name="idate_month" onChange="return ValNumero(this);" />
  <br /><br />
  Year: <input type="text" size="4" maxlength="4" name="idate_year" onChange="return ValNumero(this);" />
  <br /><br />
 </fieldset>
 <br /> 
 <fieldset> 
  <legend align="left">End Date</legend>
  Day: <input type="text" size="2" maxlength="2" name="edate_day" onChange="return ValNumero(this);" />
  <br /><br />
  Month: <input type="text" size="2" maxlength="2" name="edate_month" onChange="return ValNumero(this);" />
  <br /><br />
  Year: <input type="text" size="4" maxlength="4" name="edate_year" onChange="return ValNumero(this);" />
  <br /><br />
 </fieldset>
 <br />
 <input type="submit" value="Enviar"> 
</form>
