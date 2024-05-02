function myFunction() {
    var dots = document.getElementById("dots");
    var moreText = document.getElementById("more");
    var btnText = document.getElementById("myBtn");
  
    if (dots.style.display === "none") {
      dots.style.display = "inline";
      btnText.innerHTML = '<i class="fa-solid fa-plus"></i>';
      moreText.style.display = "none";
    } else {
      dots.style.display = "none";
      btnText.innerHTML = '<i class="fa-solid fa-minus"></i>';
      moreText.style.display = "inline";
    }
}