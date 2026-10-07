import{Y as t}from"./ui-DkJ5Ue3T.js";const r="favorites",s={index(){return t.get(`${r}`)},store(e){return t.post(`${r}`,{product_id:e})},delete(e){return t.delete(`${r}/${e}`)}};export{s as F};
