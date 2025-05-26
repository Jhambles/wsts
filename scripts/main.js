fetch('data/products.xml')
  .then(response => response.text())
  .then(str => new window.DOMParser().parseFromString(str, "text/xml"))
  .then(data => {
    const products = data.getElementsByTagName("product");
    let html = "";
    for (let i = 0; i < products.length; i++) {
      const name = products[i].getElementsByTagName("name")[0].textContent;
      const price = products[i].getElementsByTagName("price")[0].textContent;
      const image = products[i].getElementsByTagName("image")[0].textContent;
      html += `<div class="product">
                  <img src="${image}" alt="${name}" />
                  <h3>${name}</h3>
                  <p>$${price}</p>
               </div>`;
    }
    document.getElementById("product-list").innerHTML = html;
  });
