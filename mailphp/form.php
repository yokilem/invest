<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html><head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Sistem Uzmanlarına Sorunuz</title>
<script language="javascript">
function checkform(form1)
{
//alert ("indexi 1  "+ document.form1.uzman.selectedIndex);
//alert ("indexi 2  "+ document.form1.uzman.options[0].value);
   var  sel = document.form1.uzman.selectedIndex;
   if ( sel < 1 ) {
   alert("Lütfen Soru soracağınız uzmanı seçiniz.!");
   form1.uzman.focus();
   return (false);  }
   
//    form1.uzman.focus();
  if (form1.adsoyad.value == "")  {
    alert("Lütfen Adınızı giriniz !");
    form1.adsoyad.focus();
	return (false); }
  if (form1.adsoyad.value.length < 5 )  {
    alert("Lütfen Adınızı tam giriniz !");
    form1.adsoyad.focus();
	return (false); }
  if (form1.mailiniz.value == "")  {
    alert("Lütfen mail adresinizi giriniz !");
    form1.mailiniz.focus();
	return (false); }
  if (form1.konu.value == "")  {
    alert("Lütfen konu belirtiniz !");
    form1.konu.focus();
	return (false); }
  if (form1.telefon.value == "")  {
    alert("Lütfen telefon belirtiniz !");
    form1.telefon.focus();
	return (false); }
  if (form1.yer.value == "")  {
    alert("Lütfen yer belirtiniz !");
    form1.yer.focus();
	return (false); }
  if (form1.mesaj.value == "")  {
    alert("Lütfen bir mesaj giriniz !");
    form1.mesaj.focus();
	return (false); }
  if (form1.mesaj.value.length < 15 )  {
    alert("Lüten derdinizi anlatabilecek bir cümle giriniz !");
    form1.mesaj.focus();
	return (false); }
	
}
</script>
<style type="text/css">
<!--
.icyaziii {
	font-family: Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
	color: #333;
}
.icyx {
	font-family: Arial, Helvetica, sans-serif;
	font-size: 12px;
	color: #C00;
}
body,td,th {
	font-family: Arial, Helvetica, sans-serif;
}
-->
</style>
</head>
<body>
<form name="form1" method="post" action="gonder.php"  onsubmit="return checkform(this);">
<table width="100%" border="0" cellpadding="5" cellspacing="0">
 <tr align="center">
   <td colspan="2"><table width="600" border="0" align="center" cellpadding="0" cellspacing="0">
     <tr>
       <td class="icyx"> Sorunuz sistem uzmanlarımız tarafından değerlendirilip en kısa sürede tarafınıza <strong>email ile</strong> bilgi verilecektir. Lütfen email adresinizi doğru yazınız. </td>
       </tr>
     </table></td>
 </tr>
 <tr>
   <td align="right">&nbsp;</td>
   <td>&nbsp;</td>
 </tr>
 <tr>
   <td width="" align="right"><div align="right"><span class="icyaziii">Ad&#305;n&#305;z Soyad&#305;n&#305;z: </span></div></td>
  <td width=""><span class="icyaziii">
    <input name="adsoyad" type="text" id="adsoyad" size="50" maxlength="40">
    *</span></td>
</tr>
<tr>
<td align="right"><span class="icyaziii">E-posta adresiniz: </span></td>
<td><span class="icyaziii">
  <input name="mailiniz" type="text" id="mailiniz" size="50">
*</span></td>
</tr>
<tr>
<td align="right"><span class="icyaziii">Telefon numaran&#305;z:</span></td>
<td><span class="icyaziii">
  <input name="telefon" type="text" id="telefon" size="50"> 
  *
</span></td>
</tr>
<tr>
  <td align="right"><span class="icyaziii">Ya&#351;ad&#305;&#287;&#305;n&#305;z yer: </span></td>
  <td><span class="icyaziii">
    <input name="yer" type="text" id="yer" size="50">
  *</span></td>
</tr>
<tr>
  <td align="right"><span class="icyaziii">Soruyu Soraca&#287;&#305;n&#305;z Sistem Uzmanı:</span></td>
  <td><span class="icyaziii">
    <select name="uzman" id="uzman">
      <option value="1">Natro Uzaktan Destek</option>
      <option value="2">Natro Çağrı Merkezi</option>

    </select>
  *</span></td>
</tr>
<tr>
  <td align="right"><span class="icyaziii">Konu:</span></td>
  <td><span class="icyaziii">
    <input name="konu" type="text" id="konu" size="50">
  *</span></td>
</tr>
<tr>
<td align="right"><span class="icyaziii">Mesaj&#305;n&#305;z: </span></td>
<td><span class="icyaziii">
  <textarea name="mesaj" cols="42" rows="6" id="mesaj"></textarea>
*</span></td>
</tr>
<tr>
 <td rowspan="2" align="right">&nbsp;</td>
 <td height="23">&nbsp;</td>
</tr>
<tr>
 <td height="23"><input type="submit" name="Submit" value="G&ouml;nder">
   <input type="reset" name="Reset" value="Formu Temizle"></td>
</tr>
</table>
</form>
</body>
</html>