const fs = require("fs");
const path = require("path");
const html = fs.readFileSync(
  path.join(process.cwd(), "bin", "svg-html.html"),
  "utf-8"
);

const jsdom = require("jsdom");
const { JSDOM } = jsdom;

/*function parseHtml(html) {
  html = html.replace(/\s+/g, " ");

  const svgRe = /<svg(.*?)>(.*?)<\/svg>/g;

  var matches,
    output = [];
  while ((matches = svgRe.exec(html))) {
    output.push(matches[1]);
  }
  console.log(output.length);
}
parseHtml(html);
*/

const dom = new JSDOM(html);
Array.from(dom.window.document.querySelectorAll(".item")).forEach((div) => {
  const svg = div.getElementsByTagName("svg")[0];
  const name = div.getElementsByClassName("name")[0].textContent;
  //console.log(name);
  const svgHtml = svg.outerHTML.replace(/\s+/g, " ");

  if (svgHtml && name) {
    console.log(`writing ${name}`);
    fs.writeFileSync(
      path.join(process.cwd(), "resources", "assets", "svg", `${name}.svg`),
      svgHtml
    );
  }
});
