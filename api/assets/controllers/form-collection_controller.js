import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
  static targets = ["collectionContainer"];

  static values = {
    index: Number,
    prototype: String,
  };

  addCollectionElement() {
    const item = document.createElement("li");
    item.innerHTML = this.prototypeValue.replace(/__name__/g, this.indexValue);
    this.collectionContainerTarget.appendChild(item);
    this.indexValue++;
    this.addDeleteLink(item);
  }

  addDeleteLink(item) {
    const removeFormButton = document.createElement('button');
    removeFormButton.innerText = 'Supprimer';
    removeFormButton.classList.add('button', 'button--danger', 'button--small');

    item.append(removeFormButton);

    removeFormButton.addEventListener('click', (e) => {
        e.preventDefault();
        item.remove();
    });
  }
}
