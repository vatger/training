import "./chunk-G3PMV62Z.js";

// node_modules/qs-esm/lib/formats.js
var replace = String.prototype.replace;
var percentTwenties = /%20/g;
var Format = {
  RFC1738: "RFC1738",
  RFC3986: "RFC3986"
};
var formatters = {
  RFC1738: function(value) {
    return replace.call(value, percentTwenties, "+");
  },
  RFC3986: function(value) {
    return String(value);
  }
};
var RFC1738 = Format.RFC1738;
var RFC3986 = Format.RFC3986;
var formats_default = Format.RFC3986;

// node_modules/qs-esm/lib/utils.js
var has = Object.prototype.hasOwnProperty;
var isArray = Array.isArray;
var hexTable = (function() {
  const array = [];
  for (let i2 = 0; i2 < 256; ++i2) {
    array.push("%" + ((i2 < 16 ? "0" : "") + i2.toString(16)).toUpperCase());
  }
  return array;
})();
var compactQueue = function compactQueue2(queue) {
  while (queue.length > 1) {
    const item = queue.pop();
    const obj = item.obj[item.prop];
    if (isArray(obj)) {
      const compacted = [];
      for (let j = 0; j < obj.length; ++j) {
        if (typeof obj[j] !== "undefined") {
          compacted.push(obj[j]);
        }
      }
      item.obj[item.prop] = compacted;
    }
  }
};
var arrayToObject = function arrayToObject2(source, options) {
  const obj = options && options.plainObjects ? /* @__PURE__ */ Object.create(null) : {};
  for (let i2 = 0; i2 < source.length; ++i2) {
    if (typeof source[i2] !== "undefined") {
      obj[i2] = source[i2];
    }
  }
  return obj;
};
var merge = function merge2(target, source, options) {
  if (!source) {
    return target;
  }
  if (typeof source !== "object") {
    if (isArray(target)) {
      target.push(source);
    } else if (target && typeof target === "object") {
      if (options && (options.plainObjects || options.allowPrototypes) || !has.call(Object.prototype, source)) {
        target[source] = true;
      }
    } else {
      return [target, source];
    }
    return target;
  }
  if (!target || typeof target !== "object") {
    return [target].concat(source);
  }
  let mergeTarget = target;
  if (isArray(target) && !isArray(source)) {
    mergeTarget = arrayToObject(target, options);
  }
  if (isArray(target) && isArray(source)) {
    source.forEach(function(item, i2) {
      if (has.call(target, i2)) {
        const targetItem = target[i2];
        if (targetItem && typeof targetItem === "object" && item && typeof item === "object") {
          target[i2] = merge2(targetItem, item, options);
        } else {
          target.push(item);
        }
      } else {
        target[i2] = item;
      }
    });
    return target;
  }
  return Object.keys(source).reduce(function(acc, key) {
    const value = source[key];
    if (has.call(acc, key)) {
      acc[key] = merge2(acc[key], value, options);
    } else {
      acc[key] = value;
    }
    return acc;
  }, mergeTarget);
};
var decode = function(str, decoder, charset) {
  const strWithoutPlus = str.replace(/\+/g, " ");
  if (charset === "iso-8859-1") {
    return strWithoutPlus.replace(/%[0-9a-f]{2}/gi, unescape);
  }
  try {
    return decodeURIComponent(strWithoutPlus);
  } catch (e) {
    return strWithoutPlus;
  }
};
var limit = 1024;
var encode = function encode2(str, defaultEncoder, charset, kind, format) {
  if (str.length === 0) {
    return str;
  }
  let string = str;
  if (typeof str === "symbol") {
    string = Symbol.prototype.toString.call(str);
  } else if (typeof str !== "string") {
    string = String(str);
  }
  if (charset === "iso-8859-1") {
    return escape(string).replace(/%u[0-9a-f]{4}/gi, function($0) {
      return "%26%23" + parseInt($0.slice(2), 16) + "%3B";
    });
  }
  let out = "";
  for (let j = 0; j < string.length; j += limit) {
    const segment = string.length >= limit ? string.slice(j, j + limit) : string;
    const arr = [];
    for (let i2 = 0; i2 < segment.length; ++i2) {
      let c = segment.charCodeAt(i2);
      if (c === 45 || // -
      c === 46 || // .
      c === 95 || // _
      c === 126 || // ~
      c >= 48 && c <= 57 || // 0-9
      c >= 65 && c <= 90 || // a-z
      c >= 97 && c <= 122 || // A-Z
      format === RFC1738 && (c === 40 || c === 41)) {
        arr[arr.length] = segment.charAt(i2);
        continue;
      }
      if (c < 128) {
        arr[arr.length] = hexTable[c];
        continue;
      }
      if (c < 2048) {
        arr[arr.length] = hexTable[192 | c >> 6] + hexTable[128 | c & 63];
        continue;
      }
      if (c < 55296 || c >= 57344) {
        arr[arr.length] = hexTable[224 | c >> 12] + hexTable[128 | c >> 6 & 63] + hexTable[128 | c & 63];
        continue;
      }
      i2 += 1;
      c = 65536 + ((c & 1023) << 10 | segment.charCodeAt(i2) & 1023);
      arr[arr.length] = hexTable[240 | c >> 18] + hexTable[128 | c >> 12 & 63] + hexTable[128 | c >> 6 & 63] + hexTable[128 | c & 63];
    }
    out += arr.join("");
  }
  return out;
};
var compact = function compact2(value) {
  const queue = [{ obj: { o: value }, prop: "o" }];
  const refs = [];
  for (let i2 = 0; i2 < queue.length; ++i2) {
    const item = queue[i2];
    const obj = item.obj[item.prop];
    const keys = Object.keys(obj);
    for (let j = 0; j < keys.length; ++j) {
      const key = keys[j];
      const val = obj[key];
      if (typeof val === "object" && val !== null && refs.indexOf(val) === -1) {
        queue.push({ obj, prop: key });
        refs.push(val);
      }
    }
  }
  compactQueue(queue);
  return value;
};
var isRegExp = function isRegExp2(obj) {
  return Object.prototype.toString.call(obj) === "[object RegExp]";
};
var isBuffer = function isBuffer2(obj) {
  if (!obj || typeof obj !== "object") {
    return false;
  }
  return !!(obj.constructor && obj.constructor.isBuffer && obj.constructor.isBuffer(obj));
};
var combine = function combine2(a, b) {
  return [].concat(a, b);
};
var maybeMap = function maybeMap2(val, fn) {
  if (isArray(val)) {
    const mapped = [];
    for (let i2 = 0; i2 < val.length; i2 += 1) {
      mapped.push(fn(val[i2]));
    }
    return mapped;
  }
  return fn(val);
};

// node_modules/qs-esm/lib/stringify.js
var has2 = Object.prototype.hasOwnProperty;
var arrayPrefixGenerators = {
  brackets: function brackets(prefix) {
    return prefix + "[]";
  },
  comma: "comma",
  indices: function indices(prefix, key) {
    return prefix + "[" + key + "]";
  },
  repeat: function repeat(prefix) {
    return prefix;
  }
};
var isArray2 = Array.isArray;
var push = Array.prototype.push;
var pushToArray = function(arr, valueOrArray) {
  push.apply(arr, isArray2(valueOrArray) ? valueOrArray : [valueOrArray]);
};
var toISO = Date.prototype.toISOString;
var defaultFormat = formats_default;
var defaults = {
  addQueryPrefix: false,
  allowDots: false,
  allowEmptyArrays: false,
  arrayFormat: "indices",
  charset: "utf-8",
  charsetSentinel: false,
  delimiter: "&",
  encode: true,
  encodeDotInKeys: false,
  encoder: encode,
  encodeValuesOnly: false,
  format: defaultFormat,
  formatter: formatters[defaultFormat],
  // deprecated
  indices: false,
  serializeDate: function serializeDate(date) {
    return toISO.call(date);
  },
  skipNulls: false,
  strictNullHandling: false
};
var isNonNullishPrimitive = function isNonNullishPrimitive2(v) {
  return typeof v === "string" || typeof v === "number" || typeof v === "boolean" || typeof v === "symbol" || typeof v === "bigint";
};
var sentinel = {};
var _stringify = function stringify(object, prefix, generateArrayPrefix, commaRoundTrip, allowEmptyArrays, strictNullHandling, skipNulls, encodeDotInKeys, encoder, filter, sort, allowDots, serializeDate2, format, formatter, encodeValuesOnly, charset, sideChannel) {
  let obj = object;
  let tmpSc = sideChannel;
  let step = 0;
  let findFlag = false;
  while ((tmpSc = tmpSc.get(sentinel)) !== void 0 && !findFlag) {
    const pos = tmpSc.get(object);
    step += 1;
    if (typeof pos !== "undefined") {
      if (pos === step) {
        throw new RangeError("Cyclic object value");
      } else {
        findFlag = true;
      }
    }
    if (typeof tmpSc.get(sentinel) === "undefined") {
      step = 0;
    }
  }
  if (typeof filter === "function") {
    obj = filter(prefix, obj);
  } else if (obj instanceof Date) {
    obj = serializeDate2(obj);
  } else if (generateArrayPrefix === "comma" && isArray2(obj)) {
    obj = maybeMap(obj, function(value) {
      if (value instanceof Date) {
        return serializeDate2(value);
      }
      return value;
    });
  }
  if (obj === null) {
    if (strictNullHandling) {
      return encoder && !encodeValuesOnly ? encoder(prefix, defaults.encoder, charset, "key", format) : prefix;
    }
    obj = "";
  }
  if (isNonNullishPrimitive(obj) || isBuffer(obj)) {
    if (encoder) {
      const keyValue = encodeValuesOnly ? prefix : encoder(prefix, defaults.encoder, charset, "key", format);
      return [
        formatter(keyValue) + "=" + formatter(encoder(obj, defaults.encoder, charset, "value", format))
      ];
    }
    return [formatter(prefix) + "=" + formatter(String(obj))];
  }
  const values = [];
  if (typeof obj === "undefined") {
    return values;
  }
  let objKeys;
  if (generateArrayPrefix === "comma" && isArray2(obj)) {
    if (encodeValuesOnly && encoder) {
      obj = maybeMap(obj, encoder);
    }
    objKeys = [{ value: obj.length > 0 ? obj.join(",") || null : void 0 }];
  } else if (isArray2(filter)) {
    objKeys = filter;
  } else {
    const keys = Object.keys(obj);
    objKeys = sort ? keys.sort(sort) : keys;
  }
  const encodedPrefix = encodeDotInKeys ? prefix.replace(/\./g, "%2E") : prefix;
  const adjustedPrefix = commaRoundTrip && isArray2(obj) && obj.length === 1 ? encodedPrefix + "[]" : encodedPrefix;
  if (allowEmptyArrays && isArray2(obj) && obj.length === 0) {
    return adjustedPrefix + "[]";
  }
  for (let j = 0; j < objKeys.length; ++j) {
    const key = objKeys[j];
    const value = typeof key === "object" && typeof key.value !== "undefined" ? key.value : obj[key];
    if (skipNulls && value === null) {
      continue;
    }
    const encodedKey = allowDots && encodeDotInKeys ? key.replace(/\./g, "%2E") : key;
    const keyPrefix = isArray2(obj) ? typeof generateArrayPrefix === "function" ? generateArrayPrefix(adjustedPrefix, encodedKey) : adjustedPrefix : adjustedPrefix + (allowDots ? "." + encodedKey : "[" + encodedKey + "]");
    sideChannel.set(object, step);
    const valueSideChannel = /* @__PURE__ */ new WeakMap();
    valueSideChannel.set(sentinel, sideChannel);
    pushToArray(
      values,
      _stringify(
        value,
        keyPrefix,
        generateArrayPrefix,
        commaRoundTrip,
        allowEmptyArrays,
        strictNullHandling,
        skipNulls,
        encodeDotInKeys,
        generateArrayPrefix === "comma" && encodeValuesOnly && isArray2(obj) ? null : encoder,
        filter,
        sort,
        allowDots,
        serializeDate2,
        format,
        formatter,
        encodeValuesOnly,
        charset,
        valueSideChannel
      )
    );
  }
  return values;
};
var normalizeStringifyOptions = function normalizeStringifyOptions2(opts) {
  if (!opts) {
    return defaults;
  }
  if (typeof opts.allowEmptyArrays !== "undefined" && typeof opts.allowEmptyArrays !== "boolean") {
    throw new TypeError("`allowEmptyArrays` option can only be `true` or `false`, when provided");
  }
  if (typeof opts.encodeDotInKeys !== "undefined" && typeof opts.encodeDotInKeys !== "boolean") {
    throw new TypeError("`encodeDotInKeys` option can only be `true` or `false`, when provided");
  }
  if (opts.encoder !== null && typeof opts.encoder !== "undefined" && typeof opts.encoder !== "function") {
    throw new TypeError("Encoder has to be a function.");
  }
  const charset = opts.charset || defaults.charset;
  if (typeof opts.charset !== "undefined" && opts.charset !== "utf-8" && opts.charset !== "iso-8859-1") {
    throw new TypeError("The charset option must be either utf-8, iso-8859-1, or undefined");
  }
  let format = formats_default;
  if (typeof opts.format !== "undefined") {
    if (!has2.call(formatters, opts.format)) {
      throw new TypeError("Unknown format option provided.");
    }
    format = opts.format;
  }
  const formatter = formatters[format];
  let filter = defaults.filter;
  if (typeof opts.filter === "function" || isArray2(opts.filter)) {
    filter = opts.filter;
  }
  let arrayFormat;
  if (opts.arrayFormat in arrayPrefixGenerators) {
    arrayFormat = opts.arrayFormat;
  } else if ("indices" in opts) {
    arrayFormat = opts.indices ? "indices" : "repeat";
  } else {
    arrayFormat = defaults.arrayFormat;
  }
  if ("commaRoundTrip" in opts && typeof opts.commaRoundTrip !== "boolean") {
    throw new TypeError("`commaRoundTrip` must be a boolean, or absent");
  }
  const allowDots = typeof opts.allowDots === "undefined" ? opts.encodeDotInKeys === true ? true : defaults.allowDots : !!opts.allowDots;
  return {
    addQueryPrefix: typeof opts.addQueryPrefix === "boolean" ? opts.addQueryPrefix : defaults.addQueryPrefix,
    allowDots,
    allowEmptyArrays: typeof opts.allowEmptyArrays === "boolean" ? !!opts.allowEmptyArrays : defaults.allowEmptyArrays,
    arrayFormat,
    charset,
    charsetSentinel: typeof opts.charsetSentinel === "boolean" ? opts.charsetSentinel : defaults.charsetSentinel,
    commaRoundTrip: opts.commaRoundTrip,
    delimiter: typeof opts.delimiter === "undefined" ? defaults.delimiter : opts.delimiter,
    encode: typeof opts.encode === "boolean" ? opts.encode : defaults.encode,
    encodeDotInKeys: typeof opts.encodeDotInKeys === "boolean" ? opts.encodeDotInKeys : defaults.encodeDotInKeys,
    encoder: typeof opts.encoder === "function" ? opts.encoder : defaults.encoder,
    encodeValuesOnly: typeof opts.encodeValuesOnly === "boolean" ? opts.encodeValuesOnly : defaults.encodeValuesOnly,
    filter,
    format,
    formatter,
    serializeDate: typeof opts.serializeDate === "function" ? opts.serializeDate : defaults.serializeDate,
    skipNulls: typeof opts.skipNulls === "boolean" ? opts.skipNulls : defaults.skipNulls,
    sort: typeof opts.sort === "function" ? opts.sort : null,
    strictNullHandling: typeof opts.strictNullHandling === "boolean" ? opts.strictNullHandling : defaults.strictNullHandling
  };
};
function stringify2(object, opts) {
  let obj = object;
  const options = normalizeStringifyOptions(opts);
  let objKeys;
  let filter;
  if (typeof options.filter === "function") {
    filter = options.filter;
    obj = filter("", obj);
  } else if (isArray2(options.filter)) {
    filter = options.filter;
    objKeys = filter;
  }
  const keys = [];
  if (typeof obj !== "object" || obj === null) {
    return "";
  }
  const generateArrayPrefix = arrayPrefixGenerators[options.arrayFormat];
  const commaRoundTrip = generateArrayPrefix === "comma" && options.commaRoundTrip;
  if (!objKeys) {
    objKeys = Object.keys(obj);
  }
  if (options.sort) {
    objKeys.sort(options.sort);
  }
  const sideChannel = /* @__PURE__ */ new WeakMap();
  for (let i2 = 0; i2 < objKeys.length; ++i2) {
    const key = objKeys[i2];
    if (options.skipNulls && obj[key] === null) {
      continue;
    }
    pushToArray(
      keys,
      _stringify(
        obj[key],
        key,
        generateArrayPrefix,
        commaRoundTrip,
        options.allowEmptyArrays,
        options.strictNullHandling,
        options.skipNulls,
        options.encodeDotInKeys,
        options.encode ? options.encoder : null,
        options.filter,
        options.sort,
        options.allowDots,
        options.serializeDate,
        options.format,
        options.formatter,
        options.encodeValuesOnly,
        options.charset,
        sideChannel
      )
    );
  }
  const joined = keys.join(options.delimiter);
  let prefix = options.addQueryPrefix === true ? "?" : "";
  if (options.charsetSentinel) {
    if (options.charset === "iso-8859-1") {
      prefix += "utf8=%26%2310003%3B&";
    } else {
      prefix += "utf8=%E2%9C%93&";
    }
  }
  return joined.length > 0 ? prefix + joined : "";
}

// node_modules/qs-esm/lib/parse.js
var has3 = Object.prototype.hasOwnProperty;
var isArray3 = Array.isArray;
var defaults2 = {
  allowDots: false,
  allowEmptyArrays: false,
  allowPrototypes: false,
  allowSparse: false,
  arrayLimit: 20,
  charset: "utf-8",
  charsetSentinel: false,
  comma: false,
  decodeDotInKeys: false,
  decoder: decode,
  delimiter: "&",
  depth: 5,
  duplicates: "combine",
  ignoreQueryPrefix: false,
  interpretNumericEntities: false,
  parameterLimit: 1e3,
  parseArrays: true,
  plainObjects: false,
  strictNullHandling: false
};
var interpretNumericEntities = function(str) {
  return str.replace(/&#(\d+);/g, function($0, numberStr) {
    return String.fromCharCode(parseInt(numberStr, 10));
  });
};
var parseArrayValue = function(val, options) {
  if (val && typeof val === "string" && options.comma && val.indexOf(",") > -1) {
    return val.split(",");
  }
  return val;
};
var isoSentinel = "utf8=%26%2310003%3B";
var charsetSentinel = "utf8=%E2%9C%93";
var parseValues = function parseQueryStringValues(str, options) {
  const obj = { __proto__: null };
  const cleanStr = options.ignoreQueryPrefix ? str.replace(/^\?/, "") : str;
  const limit2 = options.parameterLimit === Infinity ? void 0 : options.parameterLimit;
  const parts = cleanStr.split(options.delimiter, limit2);
  let skipIndex = -1;
  let i2;
  let charset = options.charset;
  if (options.charsetSentinel) {
    for (i2 = 0; i2 < parts.length; ++i2) {
      if (parts[i2].indexOf("utf8=") === 0) {
        if (parts[i2] === charsetSentinel) {
          charset = "utf-8";
        } else if (parts[i2] === isoSentinel) {
          charset = "iso-8859-1";
        }
        skipIndex = i2;
        i2 = parts.length;
      }
    }
  }
  for (i2 = 0; i2 < parts.length; ++i2) {
    if (i2 === skipIndex) {
      continue;
    }
    const part = parts[i2];
    const bracketEqualsPos = part.indexOf("]=");
    const pos = bracketEqualsPos === -1 ? part.indexOf("=") : bracketEqualsPos + 1;
    let key, val;
    if (pos === -1) {
      key = options.decoder(part, defaults2.decoder, charset, "key");
      val = options.strictNullHandling ? null : "";
    } else {
      key = options.decoder(part.slice(0, pos), defaults2.decoder, charset, "key");
      val = maybeMap(parseArrayValue(part.slice(pos + 1), options), function(encodedVal) {
        return options.decoder(encodedVal, defaults2.decoder, charset, "value");
      });
    }
    if (val && options.interpretNumericEntities && charset === "iso-8859-1") {
      val = interpretNumericEntities(val);
    }
    if (part.indexOf("[]=") > -1) {
      val = isArray3(val) ? [val] : val;
    }
    const existing = has3.call(obj, key);
    if (existing && options.duplicates === "combine") {
      obj[key] = combine(obj[key], val);
    } else if (!existing || options.duplicates === "last") {
      obj[key] = val;
    }
  }
  return obj;
};
var parseObject = function(chain, val, options, valuesParsed) {
  let leaf = valuesParsed ? val : parseArrayValue(val, options);
  for (let i2 = chain.length - 1; i2 >= 0; --i2) {
    let obj;
    const root = chain[i2];
    if (root === "[]" && options.parseArrays) {
      obj = options.allowEmptyArrays && leaf === "" ? [] : [].concat(leaf);
    } else {
      obj = options.plainObjects ? /* @__PURE__ */ Object.create(null) : {};
      const cleanRoot = root.charAt(0) === "[" && root.charAt(root.length - 1) === "]" ? root.slice(1, -1) : root;
      const decodedRoot = options.decodeDotInKeys ? cleanRoot.replace(/%2E/g, ".") : cleanRoot;
      const index = parseInt(decodedRoot, 10);
      if (!options.parseArrays && decodedRoot === "") {
        obj = { 0: leaf };
      } else if (!isNaN(index) && root !== decodedRoot && String(index) === decodedRoot && index >= 0 && options.parseArrays && index <= options.arrayLimit) {
        obj = [];
        obj[index] = leaf;
      } else if (decodedRoot !== "__proto__") {
        obj[decodedRoot] = leaf;
      }
    }
    leaf = obj;
  }
  return leaf;
};
var parseKeys = function parseQueryStringKeys(givenKey, val, options, valuesParsed) {
  if (!givenKey) {
    return;
  }
  const key = options.allowDots ? givenKey.replace(/\.([^.[]+)/g, "[$1]") : givenKey;
  const brackets2 = /(\[[^[\]]*])/;
  const child = /(\[[^[\]]*])/g;
  let segment = options.depth > 0 && brackets2.exec(key);
  const parent = segment ? key.slice(0, segment.index) : key;
  const keys = [];
  if (parent) {
    if (!options.plainObjects && has3.call(Object.prototype, parent)) {
      if (!options.allowPrototypes) {
        return;
      }
    }
    keys.push(parent);
  }
  let i2 = 0;
  while (options.depth > 0 && (segment = child.exec(key)) !== null && i2 < options.depth) {
    i2 += 1;
    if (!options.plainObjects && has3.call(Object.prototype, segment[1].slice(1, -1))) {
      if (!options.allowPrototypes) {
        return;
      }
    }
    keys.push(segment[1]);
  }
  if (segment) {
    keys.push("[" + key.slice(segment.index) + "]");
  }
  return parseObject(keys, val, options, valuesParsed);
};
var normalizeParseOptions = function normalizeParseOptions2(opts) {
  if (!opts) {
    return defaults2;
  }
  if (typeof opts.allowEmptyArrays !== "undefined" && typeof opts.allowEmptyArrays !== "boolean") {
    throw new TypeError("`allowEmptyArrays` option can only be `true` or `false`, when provided");
  }
  if (typeof opts.decodeDotInKeys !== "undefined" && typeof opts.decodeDotInKeys !== "boolean") {
    throw new TypeError("`decodeDotInKeys` option can only be `true` or `false`, when provided");
  }
  if (opts.decoder !== null && typeof opts.decoder !== "undefined" && typeof opts.decoder !== "function") {
    throw new TypeError("Decoder has to be a function.");
  }
  if (typeof opts.charset !== "undefined" && opts.charset !== "utf-8" && opts.charset !== "iso-8859-1") {
    throw new TypeError("The charset option must be either utf-8, iso-8859-1, or undefined");
  }
  const charset = typeof opts.charset === "undefined" ? defaults2.charset : opts.charset;
  const duplicates = typeof opts.duplicates === "undefined" ? defaults2.duplicates : opts.duplicates;
  if (duplicates !== "combine" && duplicates !== "first" && duplicates !== "last") {
    throw new TypeError("The duplicates option must be either combine, first, or last");
  }
  const allowDots = typeof opts.allowDots === "undefined" ? opts.decodeDotInKeys === true ? true : defaults2.allowDots : !!opts.allowDots;
  return {
    allowDots,
    allowEmptyArrays: typeof opts.allowEmptyArrays === "boolean" ? !!opts.allowEmptyArrays : defaults2.allowEmptyArrays,
    allowPrototypes: typeof opts.allowPrototypes === "boolean" ? opts.allowPrototypes : defaults2.allowPrototypes,
    allowSparse: typeof opts.allowSparse === "boolean" ? opts.allowSparse : defaults2.allowSparse,
    arrayLimit: typeof opts.arrayLimit === "number" ? opts.arrayLimit : defaults2.arrayLimit,
    charset,
    charsetSentinel: typeof opts.charsetSentinel === "boolean" ? opts.charsetSentinel : defaults2.charsetSentinel,
    comma: typeof opts.comma === "boolean" ? opts.comma : defaults2.comma,
    decodeDotInKeys: typeof opts.decodeDotInKeys === "boolean" ? opts.decodeDotInKeys : defaults2.decodeDotInKeys,
    decoder: typeof opts.decoder === "function" ? opts.decoder : defaults2.decoder,
    delimiter: typeof opts.delimiter === "string" || isRegExp(opts.delimiter) ? opts.delimiter : defaults2.delimiter,
    // eslint-disable-next-line no-implicit-coercion, no-extra-parens
    depth: typeof opts.depth === "number" || opts.depth === false ? +opts.depth : defaults2.depth,
    duplicates,
    ignoreQueryPrefix: opts.ignoreQueryPrefix === true,
    interpretNumericEntities: typeof opts.interpretNumericEntities === "boolean" ? opts.interpretNumericEntities : defaults2.interpretNumericEntities,
    parameterLimit: typeof opts.parameterLimit === "number" ? opts.parameterLimit : defaults2.parameterLimit,
    parseArrays: opts.parseArrays !== false,
    plainObjects: typeof opts.plainObjects === "boolean" ? opts.plainObjects : defaults2.plainObjects,
    strictNullHandling: typeof opts.strictNullHandling === "boolean" ? opts.strictNullHandling : defaults2.strictNullHandling
  };
};
function parse(str, opts) {
  const options = normalizeParseOptions(opts);
  if (str === "" || str === null || typeof str === "undefined") {
    return options.plainObjects ? /* @__PURE__ */ Object.create(null) : {};
  }
  const tempObj = typeof str === "string" ? parseValues(str, options) : str;
  let obj = options.plainObjects ? /* @__PURE__ */ Object.create(null) : {};
  const keys = Object.keys(tempObj);
  for (let i2 = 0; i2 < keys.length; ++i2) {
    const key = keys[i2];
    const newObj = parseKeys(key, tempObj[key], options, typeof str === "string");
    obj = merge(obj, newObj, options);
  }
  if (options.allowSparse === true) {
    return obj;
  }
  return compact(obj);
}

// node_modules/ziggy-js/dist/index.js
function r() {
  return r = Object.assign ? Object.assign.bind() : function(t) {
    for (var e = 1; e < arguments.length; e++) {
      var r2 = arguments[e];
      for (var n2 in r2) ({}).hasOwnProperty.call(r2, n2) && (t[n2] = r2[n2]);
    }
    return t;
  }, r.apply(null, arguments);
}
var n = class {
  constructor(t, e, r2) {
    var n2, i2;
    this.name = t, this.definition = e, this.bindings = null != (n2 = e.bindings) ? n2 : {}, this.wheres = null != (i2 = e.wheres) ? i2 : {}, this.config = r2;
  }
  get template() {
    const t = `${this.origin}/${this.definition.uri}`.replace(/\/+$/, "");
    return "" === t ? "/" : t;
  }
  get origin() {
    return this.config.absolute ? this.definition.domain ? `${this.config.url.match(/^\w+:\/\//)[0]}${this.definition.domain}${this.config.port ? `:${this.config.port}` : ""}` : this.config.url : "";
  }
  get parameterSegments() {
    var t, e;
    return null != (t = null == (e = this.template.match(/{[^}?]+\??}/g)) ? void 0 : e.map((t2) => ({ name: t2.replace(/{|\??}/g, ""), required: !/\?}$/.test(t2) }))) ? t : [];
  }
  matchesUrl(e) {
    var r2;
    if (!this.definition.methods.includes("GET")) return false;
    const n2 = this.template.replace(/[.*+$()[\]]/g, "\\$&").replace(/(\/?){([^}?]*)(\??)}/g, (t, e2, r3, n3) => {
      var i3;
      const s3 = `(?<${r3}>${(null == (i3 = this.wheres[r3]) ? void 0 : i3.replace(/(^\^)|(\$$)/g, "")) || "[^/?]+"})`;
      return n3 ? `(${e2}${s3})?` : `${e2}${s3}`;
    }).replace(/^\w+:\/\//, ""), [i2, s2] = e.replace(/^\w+:\/\//, "").split("?"), o2 = null != (r2 = new RegExp(`^${n2}/?$`).exec(i2)) ? r2 : new RegExp(`^${n2}/?$`).exec(decodeURI(i2));
    if (o2) {
      for (const t in o2.groups) o2.groups[t] = "string" == typeof o2.groups[t] ? decodeURIComponent(o2.groups[t]) : o2.groups[t];
      return { params: o2.groups, query: parse(s2) };
    }
    return false;
  }
  compile(t) {
    return this.parameterSegments.length ? this.template.replace(/{([^}?]+)(\??)}/g, (e, r2, n2) => {
      var i2, s2;
      if (!n2 && [null, void 0].includes(t[r2])) throw new Error(`Ziggy error: '${r2}' parameter is required for route '${this.name}'.`);
      if (this.wheres[r2] && !new RegExp(`^${n2 ? `(${this.wheres[r2]})?` : this.wheres[r2]}$`).test(null != (s2 = t[r2]) ? s2 : "")) throw new Error(`Ziggy error: '${r2}' parameter '${t[r2]}' does not match required format '${this.wheres[r2]}' for route '${this.name}'.`);
      return encodeURI(null != (i2 = t[r2]) ? i2 : "").replace(/%7C/g, "|").replace(/%25/g, "%").replace(/\$/g, "%24");
    }).replace(this.config.absolute ? /(\.[^/]+?)(\/\/)/ : /(^)(\/\/)/, "$1/").replace(/\/+$/, "") : this.template;
  }
};
var i = class extends String {
  constructor(t, e, i2 = true, s2) {
    if (super(), this.t = null != s2 ? s2 : "undefined" != typeof Ziggy ? Ziggy : null == globalThis ? void 0 : globalThis.Ziggy, !this.t && "undefined" != typeof document && document.getElementById("ziggy-routes-json") && (globalThis.Ziggy = JSON.parse(document.getElementById("ziggy-routes-json").textContent), this.t = globalThis.Ziggy), this.t = r({}, this.t, { absolute: i2 }), t) {
      if (!this.t.routes[t]) throw new Error(`Ziggy error: route '${t}' is not in the route list.`);
      this.i = new n(t, this.t.routes[t], this.t), this.o = this.u(e);
    }
  }
  toString() {
    const t = Object.keys(this.o).filter((t2) => !this.i.parameterSegments.some(({ name: e }) => e === t2)).filter((t2) => "_query" !== t2).reduce((t2, e) => r({}, t2, { [e]: this.o[e] }), {});
    return this.i.compile(this.o) + stringify2(r({}, t, this.o._query), { addQueryPrefix: true, arrayFormat: "indices", encodeValuesOnly: true, skipNulls: true, encoder: (t2, e) => "boolean" == typeof t2 ? Number(t2) : e(t2) });
  }
  h(t) {
    t ? this.t.absolute && t.startsWith("/") && (t = this.l().host + t) : t = this.m();
    let e = {};
    const [i2, s2] = Object.entries(this.t.routes).find(([r2, i3]) => e = new n(r2, i3, this.t).matchesUrl(t)) || [void 0, void 0];
    return r({ name: i2 }, e, { route: s2 });
  }
  m() {
    const { host: t, pathname: e, search: r2 } = this.l();
    return (this.t.absolute ? t + e : e.replace(this.t.url.replace(/^\w*:\/\/[^/]+/, ""), "").replace(/^\/+/, "/")) + r2;
  }
  current(t, e) {
    const { name: i2, params: s2, query: o2, route: u2 } = this.h();
    if (!t) return i2;
    const h = new RegExp(`^${t.replace(/\./g, "\\.").replace(/\*/g, ".*")}$`).test(i2);
    if ([null, void 0].includes(e) || !h) return h;
    const a = new n(i2, u2, this.t);
    e = this.u(e, a);
    const l = r({}, s2, o2);
    if (Object.values(e).every((t2) => !t2) && !Object.values(l).some((t2) => void 0 !== t2)) return true;
    const c = (t2, e2) => Object.entries(t2).every(([t3, r2]) => Array.isArray(r2) && Array.isArray(e2[t3]) ? r2.every((r3) => e2[t3].includes(r3) || e2[t3].includes(decodeURIComponent(r3))) : "object" == typeof r2 && "object" == typeof e2[t3] && null !== r2 && null !== e2[t3] ? c(r2, e2[t3]) : e2[t3] == r2 || e2[t3] == decodeURIComponent(r2));
    return c(e, l);
  }
  l() {
    var t, e, r2, n2, i2, s2;
    const { host: o2 = "", pathname: u2 = "", search: h = "" } = "undefined" != typeof window ? window.location : {};
    return { host: null != (t = null == (e = this.t.location) ? void 0 : e.host) ? t : o2, pathname: null != (r2 = null == (n2 = this.t.location) ? void 0 : n2.pathname) ? r2 : u2, search: null != (i2 = null == (s2 = this.t.location) ? void 0 : s2.search) ? i2 : h };
  }
  get params() {
    const { params: t, query: e } = this.h();
    return r({}, t, e);
  }
  get routeParams() {
    return this.h().params;
  }
  get queryParams() {
    return this.h().query;
  }
  has(t) {
    return this.t.routes.hasOwnProperty(t);
  }
  u(t = {}, e = this.i) {
    null != t || (t = {}), t = ["string", "number"].includes(typeof t) ? [t] : t;
    const n2 = e.parameterSegments.filter(({ name: t2 }) => !this.t.defaults[t2]);
    return Array.isArray(t) ? t = t.reduce((t2, e2, i2) => r({}, t2, n2[i2] ? { [n2[i2].name]: e2 } : "object" == typeof e2 ? e2 : { [e2]: "" }), {}) : 1 !== n2.length || t[n2[0].name] || !t.hasOwnProperty(Object.values(e.bindings)[0]) && !t.hasOwnProperty("id") || (t = { [n2[0].name]: t }), r({}, this.p(e), this.$(t, e));
  }
  p(t) {
    return t.parameterSegments.filter(({ name: t2 }) => this.t.defaults[t2]).reduce((t2, { name: e }, n2) => r({}, t2, { [e]: this.t.defaults[e] }), {});
  }
  $(t, { bindings: e, parameterSegments: n2 }) {
    return Object.entries(t).reduce((t2, [i2, s2]) => {
      if (!s2 || "object" != typeof s2 || Array.isArray(s2) || !n2.some(({ name: t3 }) => t3 === i2)) return r({}, t2, { [i2]: s2 });
      if (!s2.hasOwnProperty(e[i2])) {
        if (!s2.hasOwnProperty("id")) throw new Error(`Ziggy error: object passed as '${i2}' parameter is missing route model binding key '${e[i2]}'.`);
        e[i2] = "id";
      }
      return r({}, t2, { [i2]: s2[e[i2]] });
    }, {});
  }
  valueOf() {
    return this.toString();
  }
};
function s(t, e, r2, n2) {
  const s2 = new i(t, e, r2, n2);
  return t ? s2.toString() : s2;
}
var o = { install(t, e) {
  const r2 = (t2, r3, n2, i2 = e) => s(t2, r3, n2, i2);
  parseInt(t.version) > 2 ? (t.config.globalProperties.route = r2, t.provide("route", r2)) : t.mixin({ methods: { route: r2 } });
} };
function u(t) {
  if (!t && !globalThis.Ziggy && "undefined" == typeof Ziggy && !document.getElementById("ziggy-routes-json")) throw new Error("Ziggy error: missing configuration. Ensure that a `Ziggy` variable is defined globally or pass a config object into the useRoute hook.");
  return (e, r2, n2, i2 = t) => s(e, r2, n2, i2);
}
export {
  o as ZiggyVue,
  s as route,
  u as useRoute
};
//# sourceMappingURL=ziggy-js.js.map
