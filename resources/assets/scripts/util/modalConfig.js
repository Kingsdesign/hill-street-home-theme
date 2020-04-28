export default {
  onShow: () => {
    document.body.classList.add("noscroll");
  },
  onClose: () => {
    document.body.classList.remove("noscroll");
  },
};
