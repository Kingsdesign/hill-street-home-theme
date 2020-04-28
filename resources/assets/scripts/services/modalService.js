import MicroModal from "micromodal";
import modalConfig from "../util/modalConfig";
import { addEventListener } from "../util/dom-help";

function ModalService() {
  const handlers = {
    onShow: [],
  };
  //
  console.log("modal service started");
  MicroModal.init(modalConfig);

  //Bind events
  //For some reason micromodal clsoe buttons propagate the event
  //So made our own
  addEventListener("click", "[data-modal-close]", (e, el) => {
    //MicroModal.close(el.dataset.modalClose);
    this.close(el.dataset.modalClose);
  });
  addEventListener("click", "[data-modal-trigger]", (e, el) => {
    //MicroModal.show(el.dataset.modalTrigger, modalConfig);
    this.show(el.dataset.modalTrigger, modalConfig);
  });
}
ModalService.prototype.show = function (modalId, config = {}) {
  MicroModal.show.call(null, modalId, mergeConfig(modalConfig, config));
};
ModalService.prototype.close = function () {
  MicroModal.close.apply(null, arguments);
};
ModalService.prototype.onShow = function (cb) {
  this.handlers.onShow.push(cb);
};

function mergeConfig() {
  const handlers = {
    onShow: [],
    onClose: [],
  };
  let merge = {};
  for (let i = 0; i < arguments.length; i++) {
    let arg = { ...arguments[i] };
    Object.keys(handlers).forEach((key) => {
      if (typeof arg[key] === `function`) {
        handlers[key].push(arg[key]);
      }
      if (typeof arg[key] !== `undefined`) {
        delete arg[key];
      }
    });
    merge = { ...merge, ...arg };
  }

  Object.keys(handlers).forEach((key) => {
    merge[key] = function () {
      handlers[key].forEach((handler) => handler.apply(null, arguments));
    };
  });
  return merge;
}

const ModalServiceProvider = (function () {
  let instance = window.ModalService || null;

  function createInstance() {
    var object = new ModalService();
    return object;
  }

  return {
    getInstance: function () {
      if (!instance) {
        instance = createInstance();
        window.ModalService = instance;
      }
      return instance;
    },
  };
})();

export default ModalServiceProvider.getInstance();
