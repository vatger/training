;(() => {
	var Ci = Object.create
	var he = Object.defineProperty
	var Ti = Object.getOwnPropertyDescriptor
	var Pi = Object.getOwnPropertyNames
	var xi = Object.getPrototypeOf,
		Oi = Object.prototype.hasOwnProperty
	var Ai = (l, h) => () => (h || l((h = { exports: {} }).exports, h), h.exports)
	var Ei = (l, h, a, c) => {
		if ((h && typeof h == "object") || typeof h == "function")
			for (const s of Pi(h))
				!Oi.call(l, s) &&
					s !== a &&
					he(l, s, {
						get: () => h[s],
						enumerable: !(c = Ti(h, s)) || c.enumerable,
					})
		return l
	}
	var Li = (l, h, a) => (
		(a = l != null ? Ci(xi(l)) : {}),
		Ei(
			h || !l || !l.__esModule
				? he(a, "default", { value: l, enumerable: !0 })
				: a,
			l,
		)
	)
	var me = Ai((vt, It) => {
		;((h, a) => {
			typeof vt == "object" && typeof It == "object"
				? (It.exports = a())
				: typeof define == "function" && define.amd
					? define([], a)
					: typeof vt == "object"
						? (vt.Pusher = a())
						: (h.Pusher = a())
		})(window, () =>
			((l) => {
				var h = {}
				function a(c) {
					if (h[c]) return h[c].exports
					var s = (h[c] = { i: c, l: !1, exports: {} })
					return l[c].call(s.exports, s, s.exports, a), (s.l = !0), s.exports
				}
				return (
					(a.m = l),
					(a.c = h),
					(a.d = (c, s, f) => {
						a.o(c, s) || Object.defineProperty(c, s, { enumerable: !0, get: f })
					}),
					(a.r = (c) => {
						typeof Symbol < "u" &&
							Symbol.toStringTag &&
							Object.defineProperty(c, Symbol.toStringTag, { value: "Module" }),
							Object.defineProperty(c, "__esModule", { value: !0 })
					}),
					(a.t = (c, s) => {
						if (
							(s & 1 && (c = a(c)),
							s & 8 || (s & 4 && typeof c == "object" && c && c.__esModule))
						)
							return c
						var f = Object.create(null)
						if (
							(a.r(f),
							Object.defineProperty(f, "default", { enumerable: !0, value: c }),
							s & 2 && typeof c != "string")
						)
							for (var d in c) a.d(f, d, ((N) => c[N]).bind(null, d))
						return f
					}),
					(a.n = (c) => {
						var s = c && c.__esModule ? () => c.default : () => c
						return a.d(s, "a", s), s
					}),
					(a.o = (c, s) => Object.hasOwn(c, s)),
					(a.p = ""),
					a((a.s = 2))
				)
			})([
				function (l, h, a) {
					var c =
						(this && this.__extends) ||
						(() => {
							var b = (v, y) => (
								(b =
									Object.setPrototypeOf ||
									({ __proto__: [] } instanceof Array &&
										((w, O) => {
											w.__proto__ = O
										})) ||
									((w, O) => {
										for (var I in O) Object.hasOwn(O, I) && (w[I] = O[I])
									})),
								b(v, y)
							)
							return (v, y) => {
								b(v, y)
								function w() {
									this.constructor = v
								}
								v.prototype =
									y === null
										? Object.create(y)
										: ((w.prototype = y.prototype), new w())
							}
						})()
					Object.defineProperty(h, "__esModule", { value: !0 })
					var s = 256,
						f = (() => {
							function b(v) {
								v === void 0 && (v = "="), (this._paddingCharacter = v)
							}
							return (
								(b.prototype.encodedLength = function (v) {
									return this._paddingCharacter
										? (((v + 2) / 3) * 4) | 0
										: ((v * 8 + 5) / 6) | 0
								}),
								(b.prototype.encode = function (v) {
									for (var y = "", w = 0; w < v.length - 2; w += 3) {
										var O = (v[w] << 16) | (v[w + 1] << 8) | v[w + 2]
										;(y += this._encodeByte((O >>> 18) & 63)),
											(y += this._encodeByte((O >>> 12) & 63)),
											(y += this._encodeByte((O >>> 6) & 63)),
											(y += this._encodeByte((O >>> 0) & 63))
									}
									var I = v.length - w
									if (I > 0) {
										var O = (v[w] << 16) | (I === 2 ? v[w + 1] << 8 : 0)
										;(y += this._encodeByte((O >>> 18) & 63)),
											(y += this._encodeByte((O >>> 12) & 63)),
											I === 2
												? (y += this._encodeByte((O >>> 6) & 63))
												: (y += this._paddingCharacter || ""),
											(y += this._paddingCharacter || "")
									}
									return y
								}),
								(b.prototype.maxDecodedLength = function (v) {
									return this._paddingCharacter
										? ((v / 4) * 3) | 0
										: ((v * 6 + 7) / 8) | 0
								}),
								(b.prototype.decodedLength = function (v) {
									return this.maxDecodedLength(
										v.length - this._getPaddingLength(v),
									)
								}),
								(b.prototype.decode = function (v) {
									if (v.length === 0) return new Uint8Array(0)
									for (
										var y = this._getPaddingLength(v),
											w = v.length - y,
											O = new Uint8Array(this.maxDecodedLength(w)),
											I = 0,
											q = 0,
											M = 0,
											J = 0,
											F = 0,
											z = 0,
											B = 0;
										q < w - 4;
										q += 4
									)
										(J = this._decodeChar(v.charCodeAt(q + 0))),
											(F = this._decodeChar(v.charCodeAt(q + 1))),
											(z = this._decodeChar(v.charCodeAt(q + 2))),
											(B = this._decodeChar(v.charCodeAt(q + 3))),
											(O[I++] = (J << 2) | (F >>> 4)),
											(O[I++] = (F << 4) | (z >>> 2)),
											(O[I++] = (z << 6) | B),
											(M |= J & s),
											(M |= F & s),
											(M |= z & s),
											(M |= B & s)
									if (
										(q < w - 1 &&
											((J = this._decodeChar(v.charCodeAt(q))),
											(F = this._decodeChar(v.charCodeAt(q + 1))),
											(O[I++] = (J << 2) | (F >>> 4)),
											(M |= J & s),
											(M |= F & s)),
										q < w - 2 &&
											((z = this._decodeChar(v.charCodeAt(q + 2))),
											(O[I++] = (F << 4) | (z >>> 2)),
											(M |= z & s)),
										q < w - 3 &&
											((B = this._decodeChar(v.charCodeAt(q + 3))),
											(O[I++] = (z << 6) | B),
											(M |= B & s)),
										M !== 0)
									)
										throw new Error(
											"Base64Coder: incorrect characters for decoding",
										)
									return O
								}),
								(b.prototype._encodeByte = (v) => {
									var y = v
									return (
										(y += 65),
										(y += ((25 - v) >>> 8) & 6),
										(y += ((51 - v) >>> 8) & -75),
										(y += ((61 - v) >>> 8) & -15),
										(y += ((62 - v) >>> 8) & 3),
										String.fromCharCode(y)
									)
								}),
								(b.prototype._decodeChar = (v) => {
									var y = s
									return (
										(y += (((42 - v) & (v - 44)) >>> 8) & (-s + v - 43 + 62)),
										(y += (((46 - v) & (v - 48)) >>> 8) & (-s + v - 47 + 63)),
										(y += (((47 - v) & (v - 58)) >>> 8) & (-s + v - 48 + 52)),
										(y += (((64 - v) & (v - 91)) >>> 8) & (-s + v - 65 + 0)),
										(y += (((96 - v) & (v - 123)) >>> 8) & (-s + v - 97 + 26)),
										y
									)
								}),
								(b.prototype._getPaddingLength = function (v) {
									var y = 0
									if (this._paddingCharacter) {
										for (
											var w = v.length - 1;
											w >= 0 && v[w] === this._paddingCharacter;
											w--
										)
											y++
										if (v.length < 4 || y > 2)
											throw new Error("Base64Coder: incorrect padding")
									}
									return y
								}),
								b
							)
						})()
					h.Coder = f
					var d = new f()
					function N(b) {
						return d.encode(b)
					}
					h.encode = N
					function P(b) {
						return d.decode(b)
					}
					h.decode = P
					var T = ((b) => {
						c(v, b)
						function v() {
							return (b !== null && b.apply(this, arguments)) || this
						}
						return (
							(v.prototype._encodeByte = (y) => {
								var w = y
								return (
									(w += 65),
									(w += ((25 - y) >>> 8) & 6),
									(w += ((51 - y) >>> 8) & -75),
									(w += ((61 - y) >>> 8) & -13),
									(w += ((62 - y) >>> 8) & 49),
									String.fromCharCode(w)
								)
							}),
							(v.prototype._decodeChar = (y) => {
								var w = s
								return (
									(w += (((44 - y) & (y - 46)) >>> 8) & (-s + y - 45 + 62)),
									(w += (((94 - y) & (y - 96)) >>> 8) & (-s + y - 95 + 63)),
									(w += (((47 - y) & (y - 58)) >>> 8) & (-s + y - 48 + 52)),
									(w += (((64 - y) & (y - 91)) >>> 8) & (-s + y - 65 + 0)),
									(w += (((96 - y) & (y - 123)) >>> 8) & (-s + y - 97 + 26)),
									w
								)
							}),
							v
						)
					})(f)
					h.URLSafeCoder = T
					var S = new T()
					function C(b) {
						return S.encode(b)
					}
					h.encodeURLSafe = C
					function x(b) {
						return S.decode(b)
					}
					;(h.decodeURLSafe = x),
						(h.encodedLength = (b) => d.encodedLength(b)),
						(h.maxDecodedLength = (b) => d.maxDecodedLength(b)),
						(h.decodedLength = (b) => d.decodedLength(b))
				},
				(l, h, a) => {
					Object.defineProperty(h, "__esModule", { value: !0 })
					var c = "utf8: invalid string",
						s = "utf8: invalid source encoding"
					function f(P) {
						for (
							var T = new Uint8Array(d(P)), S = 0, C = 0;
							C < P.length;
							C++
						) {
							var x = P.charCodeAt(C)
							x < 128
								? (T[S++] = x)
								: x < 2048
									? ((T[S++] = 192 | (x >> 6)), (T[S++] = 128 | (x & 63)))
									: x < 55296
										? ((T[S++] = 224 | (x >> 12)),
											(T[S++] = 128 | ((x >> 6) & 63)),
											(T[S++] = 128 | (x & 63)))
										: (C++,
											(x = (x & 1023) << 10),
											(x |= P.charCodeAt(C) & 1023),
											(x += 65536),
											(T[S++] = 240 | (x >> 18)),
											(T[S++] = 128 | ((x >> 12) & 63)),
											(T[S++] = 128 | ((x >> 6) & 63)),
											(T[S++] = 128 | (x & 63)))
						}
						return T
					}
					h.encode = f
					function d(P) {
						for (var T = 0, S = 0; S < P.length; S++) {
							var C = P.charCodeAt(S)
							if (C < 128) T += 1
							else if (C < 2048) T += 2
							else if (C < 55296) T += 3
							else if (C <= 57343) {
								if (S >= P.length - 1) throw new Error(c)
								S++, (T += 4)
							} else throw new Error(c)
						}
						return T
					}
					h.encodedLength = d
					function N(P) {
						for (var T = [], S = 0; S < P.length; S++) {
							var C = P[S]
							if (C & 128) {
								var x = void 0
								if (C < 224) {
									if (S >= P.length) throw new Error(s)
									var b = P[++S]
									if ((b & 192) !== 128) throw new Error(s)
									;(C = ((C & 31) << 6) | (b & 63)), (x = 128)
								} else if (C < 240) {
									if (S >= P.length - 1) throw new Error(s)
									var b = P[++S],
										v = P[++S]
									if ((b & 192) !== 128 || (v & 192) !== 128) throw new Error(s)
									;(C = ((C & 15) << 12) | ((b & 63) << 6) | (v & 63)),
										(x = 2048)
								} else if (C < 248) {
									if (S >= P.length - 2) throw new Error(s)
									var b = P[++S],
										v = P[++S],
										y = P[++S]
									if (
										(b & 192) !== 128 ||
										(v & 192) !== 128 ||
										(y & 192) !== 128
									)
										throw new Error(s)
									;(C =
										((C & 15) << 18) |
										((b & 63) << 12) |
										((v & 63) << 6) |
										(y & 63)),
										(x = 65536)
								} else throw new Error(s)
								if (C < x || (C >= 55296 && C <= 57343)) throw new Error(s)
								if (C >= 65536) {
									if (C > 1114111) throw new Error(s)
									;(C -= 65536),
										T.push(String.fromCharCode(55296 | (C >> 10))),
										(C = 56320 | (C & 1023))
								}
							}
							T.push(String.fromCharCode(C))
						}
						return T.join("")
					}
					h.decode = N
				},
				(l, h, a) => {
					l.exports = a(3).default
				},
				(l, h, a) => {
					a.r(h)
					var c = (() => {
							function e(t, n) {
								;(this.lastId = 0), (this.prefix = t), (this.name = n)
							}
							return (
								(e.prototype.create = function (t) {
									this.lastId++
									var n = this.lastId,
										r = this.prefix + n,
										i = this.name + "[" + n + "]",
										o = !1,
										u = function () {
											o || (t.apply(null, arguments), (o = !0))
										}
									return (
										(this[n] = u), { number: n, id: r, name: i, callback: u }
									)
								}),
								(e.prototype.remove = function (t) {
									delete this[t.number]
								}),
								e
							)
						})(),
						s = new c("_pusher_script_", "Pusher.ScriptReceivers"),
						f = {
							VERSION: "7.6.0",
							PROTOCOL: 7,
							wsPort: 80,
							wssPort: 443,
							wsPath: "",
							httpHost: "sockjs.pusher.com",
							httpPort: 80,
							httpsPort: 443,
							httpPath: "/pusher",
							stats_host: "stats.pusher.com",
							authEndpoint: "/pusher/auth",
							authTransport: "ajax",
							activityTimeout: 12e4,
							pongTimeout: 3e4,
							unavailableTimeout: 1e4,
							cluster: "mt1",
							userAuthentication: {
								endpoint: "/pusher/user-auth",
								transport: "ajax",
							},
							channelAuthorization: {
								endpoint: "/pusher/auth",
								transport: "ajax",
							},
							cdn_http: "http://js.pusher.com",
							cdn_https: "https://js.pusher.com",
							dependency_suffix: "",
						},
						d = f,
						N = (() => {
							function e(t) {
								;(this.options = t),
									(this.receivers = t.receivers || s),
									(this.loading = {})
							}
							return (
								(e.prototype.load = function (t, n, r) {
									if (this.loading[t] && this.loading[t].length > 0)
										this.loading[t].push(r)
									else {
										this.loading[t] = [r]
										var o = m.createScriptRequest(this.getPath(t, n)),
											u = this.receivers.create((p) => {
												if ((this.receivers.remove(u), this.loading[t])) {
													var _ = this.loading[t]
													delete this.loading[t]
													for (
														var g = (E) => {
																E || o.cleanup()
															},
															k = 0;
														k < _.length;
														k++
													)
														_[k](p, g)
												}
											})
										o.send(u)
									}
								}),
								(e.prototype.getRoot = function (t) {
									var n,
										r = m.getDocument().location.protocol
									return (
										(t && t.useTLS) || r === "https:"
											? (n = this.options.cdn_https)
											: (n = this.options.cdn_http),
										n.replace(/\/*$/, "") + "/" + this.options.version
									)
								}),
								(e.prototype.getPath = function (t, n) {
									return this.getRoot(n) + "/" + t + this.options.suffix + ".js"
								}),
								e
							)
						})(),
						P = N,
						T = new c("_pusher_dependencies", "Pusher.DependenciesReceivers"),
						S = new P({
							cdn_http: d.cdn_http,
							cdn_https: d.cdn_https,
							version: d.VERSION,
							suffix: d.dependency_suffix,
							receivers: T,
						}),
						C = {
							baseUrl: "https://pusher.com",
							urls: {
								authenticationEndpoint: {
									path: "/docs/channels/server_api/authenticating_users",
								},
								authorizationEndpoint: {
									path: "/docs/channels/server_api/authorizing-users/",
								},
								javascriptQuickStart: { path: "/docs/javascript_quick_start" },
								triggeringClientEvents: {
									path: "/docs/client_api_guide/client_events#trigger-events",
								},
								encryptedChannelSupport: {
									fullUrl:
										"https://github.com/pusher/pusher-js/tree/cc491015371a4bde5743d1c87a0fbac0feb53195#encrypted-channel-support",
								},
							},
						},
						x = (e) => {
							var t = "See:",
								n = C.urls[e]
							if (!n) return ""
							var r
							return (
								n.fullUrl
									? (r = n.fullUrl)
									: n.path && (r = C.baseUrl + n.path),
								r ? t + " " + r : ""
							)
						},
						b = { buildLogSuffix: x },
						v
					;((e) => {
						;(e.UserAuthentication = "user-authentication"),
							(e.ChannelAuthorization = "channel-authorization")
					})(v || (v = {}))
					var y = (() => {
							var e = (t, n) => (
								(e =
									Object.setPrototypeOf ||
									({ __proto__: [] } instanceof Array &&
										((r, i) => {
											r.__proto__ = i
										})) ||
									((r, i) => {
										for (var o in i) Object.hasOwn(i, o) && (r[o] = i[o])
									})),
								e(t, n)
							)
							return (t, n) => {
								e(t, n)
								function r() {
									this.constructor = t
								}
								t.prototype =
									n === null
										? Object.create(n)
										: ((r.prototype = n.prototype), new r())
							}
						})(),
						w = ((e) => {
							y(t, e)
							function t(n) {
								var r = this.constructor,
									i = e.call(this, n) || this
								return Object.setPrototypeOf(i, r.prototype), i
							}
							return t
						})(Error),
						O = ((e) => {
							y(t, e)
							function t(n) {
								var r = this.constructor,
									i = e.call(this, n) || this
								return Object.setPrototypeOf(i, r.prototype), i
							}
							return t
						})(Error),
						I = ((e) => {
							y(t, e)
							function t(n) {
								var r = this.constructor,
									i = e.call(this, n) || this
								return Object.setPrototypeOf(i, r.prototype), i
							}
							return t
						})(Error),
						q = ((e) => {
							y(t, e)
							function t(n) {
								var r = this.constructor,
									i = e.call(this, n) || this
								return Object.setPrototypeOf(i, r.prototype), i
							}
							return t
						})(Error),
						M = ((e) => {
							y(t, e)
							function t(n) {
								var r = this.constructor,
									i = e.call(this, n) || this
								return Object.setPrototypeOf(i, r.prototype), i
							}
							return t
						})(Error),
						J = ((e) => {
							y(t, e)
							function t(n) {
								var r = this.constructor,
									i = e.call(this, n) || this
								return Object.setPrototypeOf(i, r.prototype), i
							}
							return t
						})(Error),
						F = ((e) => {
							y(t, e)
							function t(n) {
								var r = this.constructor,
									i = e.call(this, n) || this
								return Object.setPrototypeOf(i, r.prototype), i
							}
							return t
						})(Error),
						z = ((e) => {
							y(t, e)
							function t(n) {
								var r = this.constructor,
									i = e.call(this, n) || this
								return Object.setPrototypeOf(i, r.prototype), i
							}
							return t
						})(Error),
						B = ((e) => {
							y(t, e)
							function t(n, r) {
								var i = this.constructor,
									o = e.call(this, r) || this
								return (o.status = n), Object.setPrototypeOf(o, i.prototype), o
							}
							return t
						})(Error),
						ke = (e, t, n, r, i) => {
							var o = m.createXHR()
							o.open("POST", n.endpoint, !0),
								o.setRequestHeader(
									"Content-Type",
									"application/x-www-form-urlencoded",
								)
							for (var u in n.headers) o.setRequestHeader(u, n.headers[u])
							if (n.headersProvider != null) {
								var p = n.headersProvider()
								for (var u in p) o.setRequestHeader(u, p[u])
							}
							return (
								(o.onreadystatechange = () => {
									if (o.readyState === 4)
										if (o.status === 200) {
											var _ = void 0,
												g = !1
											try {
												;(_ = JSON.parse(o.responseText)), (g = !0)
											} catch {
												i(
													new B(
														200,
														"JSON returned from " +
															r.toString() +
															" endpoint was invalid, yet status code was 200. Data was: " +
															o.responseText,
													),
													null,
												)
											}
											g && i(null, _)
										} else {
											var k = ""
											switch (r) {
												case v.UserAuthentication:
													k = b.buildLogSuffix("authenticationEndpoint")
													break
												case v.ChannelAuthorization:
													k =
														"Clients must be authorized to join private or presence channels. " +
														b.buildLogSuffix("authorizationEndpoint")
													break
											}
											i(
												new B(
													o.status,
													"Unable to retrieve auth string from " +
														r.toString() +
														" endpoint - " +
														("received status: " +
															o.status +
															" from " +
															n.endpoint +
															". " +
															k),
												),
												null,
											)
										}
								}),
								o.send(t),
								o
							)
						},
						Se = ke
					function Ce(e) {
						return Ee(Oe(e))
					}
					for (
						var nt = String.fromCharCode,
							Z =
								"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/",
							Te = {},
							ct = 0,
							Pe = Z.length;
						ct < Pe;
						ct++
					)
						Te[Z.charAt(ct)] = ct
					var xe = (e) => {
							var t = e.charCodeAt(0)
							return t < 128
								? e
								: t < 2048
									? nt(192 | (t >>> 6)) + nt(128 | (t & 63))
									: nt(224 | ((t >>> 12) & 15)) +
										nt(128 | ((t >>> 6) & 63)) +
										nt(128 | (t & 63))
						},
						Oe = (e) => e.replace(/[^\x00-\x7F]/g, xe),
						Ae = (e) => {
							var t = [0, 2, 1][e.length % 3],
								n =
									(e.charCodeAt(0) << 16) |
									((e.length > 1 ? e.charCodeAt(1) : 0) << 8) |
									(e.length > 2 ? e.charCodeAt(2) : 0),
								r = [
									Z.charAt(n >>> 18),
									Z.charAt((n >>> 12) & 63),
									t >= 2 ? "=" : Z.charAt((n >>> 6) & 63),
									t >= 1 ? "=" : Z.charAt(n & 63),
								]
							return r.join("")
						},
						Ee = window.btoa || ((e) => e.replace(/[\s\S]{1,3}/g, Ae)),
						Le = (() => {
							function e(t, n, r, i) {

								;(this.clear = n),
									(this.timer = t(() => {
										this.timer && (this.timer = i(this.timer))
									}, r))
							}
							return (
								(e.prototype.isRunning = function () {
									return this.timer !== null
								}),
								(e.prototype.ensureAborted = function () {
									this.timer && (this.clear(this.timer), (this.timer = null))
								}),
								e
							)
						})(),
						jt = Le,
						Nt = (() => {
							var e = (t, n) => (
								(e =
									Object.setPrototypeOf ||
									({ __proto__: [] } instanceof Array &&
										((r, i) => {
											r.__proto__ = i
										})) ||
									((r, i) => {
										for (var o in i) Object.hasOwn(i, o) && (r[o] = i[o])
									})),
								e(t, n)
							)
							return (t, n) => {
								e(t, n)
								function r() {
									this.constructor = t
								}
								t.prototype =
									n === null
										? Object.create(n)
										: ((r.prototype = n.prototype), new r())
							}
						})()
					function Re(e) {
						window.clearTimeout(e)
					}
					function Ie(e) {
						window.clearInterval(e)
					}
					var Q = ((e) => {
							Nt(t, e)
							function t(n, r) {
								return (
									e.call(this, setTimeout, Re, n, (i) => (r(), null)) || this
								)
							}
							return t
						})(jt),
						je = ((e) => {
							Nt(t, e)
							function t(n, r) {
								return e.call(this, setInterval, Ie, n, (i) => (r(), i)) || this
							}
							return t
						})(jt),
						Ne = {
							now: () => (Date.now ? Date.now() : new Date().valueOf()),
							defer: (e) => new Q(0, e),
							method: function (e) {
								for (var t = [], n = 1; n < arguments.length; n++)
									t[n - 1] = arguments[n]
								var r = Array.prototype.slice.call(arguments, 1)
								return function (i) {
									return i[e].apply(i, r.concat(arguments))
								}
							},
						},
						j = Ne
					function U(e) {
						for (var t = [], n = 1; n < arguments.length; n++)
							t[n - 1] = arguments[n]
						for (var r = 0; r < t.length; r++) {
							var i = t[r]
							for (var o in i)
								i[o] && i[o].constructor && i[o].constructor === Object
									? (e[o] = U(e[o] || {}, i[o]))
									: (e[o] = i[o])
						}
						return e
					}
					function qe() {
						for (var e = ["Pusher"], t = 0; t < arguments.length; t++)
							typeof arguments[t] == "string"
								? e.push(arguments[t])
								: e.push(ut(arguments[t]))
						return e.join(" : ")
					}
					function qt(e, t) {
						var n = Array.prototype.indexOf
						if (e === null) return -1
						if (n && e.indexOf === n) return e.indexOf(t)
						for (var r = 0, i = e.length; r < i; r++) if (e[r] === t) return r
						return -1
					}
					function W(e, t) {
						for (var n in e) Object.hasOwn(e, n) && t(e[n], n, e)
					}
					function Ut(e) {
						var t = []
						return (
							W(e, (n, r) => {
								t.push(r)
							}),
							t
						)
					}
					function Ue(e) {
						var t = []
						return (
							W(e, (n) => {
								t.push(n)
							}),
							t
						)
					}
					function rt(e, t, n) {
						for (var r = 0; r < e.length; r++) t.call(n || window, e[r], r, e)
					}
					function Dt(e, t) {
						for (var n = [], r = 0; r < e.length; r++) n.push(t(e[r], r, e, n))
						return n
					}
					function De(e, t) {
						var n = {}
						return (
							W(e, (r, i) => {
								n[i] = t(r)
							}),
							n
						)
					}
					function Ht(e, t) {
						t = t || ((i) => !!i)
						for (var n = [], r = 0; r < e.length; r++)
							t(e[r], r, e, n) && n.push(e[r])
						return n
					}
					function Mt(e, t) {
						var n = {}
						return (
							W(e, (r, i) => {
								;((t && t(r, i, e, n)) || r) && (n[i] = r)
							}),
							n
						)
					}
					function He(e) {
						var t = []
						return (
							W(e, (n, r) => {
								t.push([r, n])
							}),
							t
						)
					}
					function zt(e, t) {
						for (var n = 0; n < e.length; n++) if (t(e[n], n, e)) return !0
						return !1
					}
					function Me(e, t) {
						for (var n = 0; n < e.length; n++) if (!t(e[n], n, e)) return !1
						return !0
					}
					function ze(e) {
						return De(
							e,
							(t) => (
								typeof t == "object" && (t = ut(t)),
								encodeURIComponent(Ce(t.toString()))
							),
						)
					}
					function Fe(e) {
						var t = Mt(e, (r) => r !== void 0),
							n = Dt(He(ze(t)), j.method("join", "=")).join("&")
						return n
					}
					function Be(e) {
						var t = [],
							n = []
						return (function r(i, o) {
							var u, p, _
							switch (typeof i) {
								case "object":
									if (!i) return null
									for (u = 0; u < t.length; u += 1)
										if (t[u] === i) return { $ref: n[u] }
									if (
										(t.push(i),
										n.push(o),
										Object.prototype.toString.apply(i) === "[object Array]")
									)
										for (_ = [], u = 0; u < i.length; u += 1)
											_[u] = r(i[u], o + "[" + u + "]")
									else {
										_ = {}
										for (p in i)
											Object.hasOwn(i, p) &&
												(_[p] = r(i[p], o + "[" + JSON.stringify(p) + "]"))
									}
									return _
								case "number":
								case "string":
								case "boolean":
									return i
							}
						})(e, "$")
					}
					function ut(e) {
						try {
							return JSON.stringify(e)
						} catch {
							return JSON.stringify(Be(e))
						}
					}
					var Xe = (() => {
							function e() {
								this.globalLog = (t) => {
									window.console && window.console.log && window.console.log(t)
								}
							}
							return (
								(e.prototype.debug = function () {
									for (var t = [], n = 0; n < arguments.length; n++)
										t[n] = arguments[n]
									this.log(this.globalLog, t)
								}),
								(e.prototype.warn = function () {
									for (var t = [], n = 0; n < arguments.length; n++)
										t[n] = arguments[n]
									this.log(this.globalLogWarn, t)
								}),
								(e.prototype.error = function () {
									for (var t = [], n = 0; n < arguments.length; n++)
										t[n] = arguments[n]
									this.log(this.globalLogError, t)
								}),
								(e.prototype.globalLogWarn = function (t) {
									window.console && window.console.warn
										? window.console.warn(t)
										: this.globalLog(t)
								}),
								(e.prototype.globalLogError = function (t) {
									window.console && window.console.error
										? window.console.error(t)
										: this.globalLogWarn(t)
								}),
								(e.prototype.log = function (t) {
									for (var n = [], r = 1; r < arguments.length; r++)
										n[r - 1] = arguments[r]
									var i = qe.apply(this, arguments)
									if (Pt.log) Pt.log(i)
									else if (Pt.logToConsole) {
										var o = t.bind(this)
										o(i)
									}
								}),
								e
							)
						})(),
						A = new Xe(),
						Je = (e, t, n, r, i) => {
							;(n.headers !== void 0 || n.headersProvider != null) &&
								A.warn(
									"To send headers with the " +
										r.toString() +
										" request, you must use AJAX, rather than JSONP.",
								)
							var o = e.nextAuthCallbackID.toString()
							e.nextAuthCallbackID++
							var u = e.getDocument(),
								p = u.createElement("script")
							e.auth_callbacks[o] = (k) => {
								i(null, k)
							}
							var _ = "Pusher.auth_callbacks['" + o + "']"
							p.src =
								n.endpoint + "?callback=" + encodeURIComponent(_) + "&" + t
							var g = u.getElementsByTagName("head")[0] || u.documentElement
							g.insertBefore(p, g.firstChild)
						},
						We = Je,
						Ve = (() => {
							function e(t) {
								this.src = t
							}
							return (
								(e.prototype.send = function (t) {
									var r = "Error loading " + this.src
									;(this.script = document.createElement("script")),
										(this.script.id = t.id),
										(this.script.src = this.src),
										(this.script.type = "text/javascript"),
										(this.script.charset = "UTF-8"),
										this.script.addEventListener
											? ((this.script.onerror = () => {
													t.callback(r)
												}),
												(this.script.onload = () => {
													t.callback(null)
												}))
											: (this.script.onreadystatechange = () => {
													;(this.script.readyState === "loaded" ||
														this.script.readyState === "complete") &&
														t.callback(null)
												}),
										this.script.async === void 0 &&
										document.attachEvent &&
										/opera/i.test(navigator.userAgent)
											? ((this.errorScript = document.createElement("script")),
												(this.errorScript.id = t.id + "_error"),
												(this.errorScript.text = t.name + "('" + r + "');"),
												(this.script.async = this.errorScript.async = !1))
											: (this.script.async = !0)
									var i = document.getElementsByTagName("head")[0]
									i.insertBefore(this.script, i.firstChild),
										this.errorScript &&
											i.insertBefore(this.errorScript, this.script.nextSibling)
								}),
								(e.prototype.cleanup = function () {
									this.script &&
										((this.script.onload = this.script.onerror = null),
										(this.script.onreadystatechange = null)),
										this.script &&
											this.script.parentNode &&
											this.script.parentNode.removeChild(this.script),
										this.errorScript &&
											this.errorScript.parentNode &&
											this.errorScript.parentNode.removeChild(this.errorScript),
										(this.script = null),
										(this.errorScript = null)
								}),
								e
							)
						})(),
						Ge = Ve,
						Qe = (() => {
							function e(t, n) {
								;(this.url = t), (this.data = n)
							}
							return (
								(e.prototype.send = function (t) {
									if (!this.request) {
										var n = Fe(this.data),
											r = this.url + "/" + t.number + "?" + n
										;(this.request = m.createScriptRequest(r)),
											this.request.send(t)
									}
								}),
								(e.prototype.cleanup = function () {
									this.request && this.request.cleanup()
								}),
								e
							)
						})(),
						Ke = Qe,
						Ye = (e, t) => (n, r) => {
							var i = "http" + (t ? "s" : "") + "://",
								o = i + (e.host || e.options.host) + e.options.path,
								u = m.createJSONPRequest(o, n),
								p = m.ScriptReceivers.create((_, g) => {
									s.remove(p),
										u.cleanup(),
										g && g.host && (e.host = g.host),
										r && r(_, g)
								})
							u.send(p)
						},
						$e = { name: "jsonp", getAgent: Ye },
						Ze = $e
					function yt(e, t, n) {
						var r = e + (t.useTLS ? "s" : ""),
							i = t.useTLS ? t.hostTLS : t.hostNonTLS
						return r + "://" + i + n
					}
					function gt(e, t) {
						var n = "/app/" + e,
							r =
								"?protocol=" +
								d.PROTOCOL +
								"&client=js&version=" +
								d.VERSION +
								(t ? "&" + t : "")
						return n + r
					}
					var tn = {
							getInitial: (e, t) => {
								var n = (t.httpPath || "") + gt(e, "flash=false")
								return yt("ws", t, n)
							},
						},
						en = {
							getInitial: (e, t) => {
								var n = (t.httpPath || "/pusher") + gt(e)
								return yt("http", t, n)
							},
						},
						nn = {
							getInitial: (e, t) => yt("http", t, t.httpPath || "/pusher"),
							getPath: (e, t) => gt(e),
						},
						rn = (() => {
							function e() {
								this._callbacks = {}
							}
							return (
								(e.prototype.get = function (t) {
									return this._callbacks[_t(t)]
								}),
								(e.prototype.add = function (t, n, r) {
									var i = _t(t)
									;(this._callbacks[i] = this._callbacks[i] || []),
										this._callbacks[i].push({ fn: n, context: r })
								}),
								(e.prototype.remove = function (t, n, r) {
									if (!t && !n && !r) {
										this._callbacks = {}
										return
									}
									var i = t ? [_t(t)] : Ut(this._callbacks)
									n || r
										? this.removeCallback(i, n, r)
										: this.removeAllCallbacks(i)
								}),
								(e.prototype.removeCallback = function (t, n, r) {
									rt(
										t,
										function (i) {
											;(this._callbacks[i] = Ht(
												this._callbacks[i] || [],
												(o) => (n && n !== o.fn) || (r && r !== o.context),
											)),
												this._callbacks[i].length === 0 &&
													delete this._callbacks[i]
										},
										this,
									)
								}),
								(e.prototype.removeAllCallbacks = function (t) {
									rt(
										t,
										function (n) {
											delete this._callbacks[n]
										},
										this,
									)
								}),
								e
							)
						})(),
						on = rn
					function _t(e) {
						return "_" + e
					}
					var sn = (() => {
							function e(t) {
								;(this.callbacks = new on()),
									(this.global_callbacks = []),
									(this.failThrough = t)
							}
							return (
								(e.prototype.bind = function (t, n, r) {
									return this.callbacks.add(t, n, r), this
								}),
								(e.prototype.bind_global = function (t) {
									return this.global_callbacks.push(t), this
								}),
								(e.prototype.unbind = function (t, n, r) {
									return this.callbacks.remove(t, n, r), this
								}),
								(e.prototype.unbind_global = function (t) {
									return t
										? ((this.global_callbacks = Ht(
												this.global_callbacks || [],
												(n) => n !== t,
											)),
											this)
										: ((this.global_callbacks = []), this)
								}),
								(e.prototype.unbind_all = function () {
									return this.unbind(), this.unbind_global(), this
								}),
								(e.prototype.emit = function (t, n, r) {
									for (var i = 0; i < this.global_callbacks.length; i++)
										this.global_callbacks[i](t, n)
									var o = this.callbacks.get(t),
										u = []
									if ((r ? u.push(n, r) : n && u.push(n), o && o.length > 0))
										for (var i = 0; i < o.length; i++)
											o[i].fn.apply(o[i].context || window, u)
									else this.failThrough && this.failThrough(t, n)
									return this
								}),
								e
							)
						})(),
						V = sn,
						an = (() => {
							var e = (t, n) => (
								(e =
									Object.setPrototypeOf ||
									({ __proto__: [] } instanceof Array &&
										((r, i) => {
											r.__proto__ = i
										})) ||
									((r, i) => {
										for (var o in i) Object.hasOwn(i, o) && (r[o] = i[o])
									})),
								e(t, n)
							)
							return (t, n) => {
								e(t, n)
								function r() {
									this.constructor = t
								}
								t.prototype =
									n === null
										? Object.create(n)
										: ((r.prototype = n.prototype), new r())
							}
						})(),
						cn = ((e) => {
							an(t, e)
							function t(n, r, i, o, u) {
								var p = e.call(this) || this
								return (
									(p.initialize = m.transportConnectionInitializer),
									(p.hooks = n),
									(p.name = r),
									(p.priority = i),
									(p.key = o),
									(p.options = u),
									(p.state = "new"),
									(p.timeline = u.timeline),
									(p.activityTimeout = u.activityTimeout),
									(p.id = p.timeline.generateUniqueID()),
									p
								)
							}
							return (
								(t.prototype.handlesActivityChecks = function () {
									return !!this.hooks.handlesActivityChecks
								}),
								(t.prototype.supportsPing = function () {
									return !!this.hooks.supportsPing
								}),
								(t.prototype.connect = function () {
									if (this.socket || this.state !== "initialized") return !1
									var r = this.hooks.urls.getInitial(this.key, this.options)
									try {
										this.socket = this.hooks.getSocket(r, this.options)
									} catch (i) {
										return (
											j.defer(() => {
												this.onError(i), this.changeState("closed")
											}),
											!1
										)
									}
									return (
										this.bindListeners(),
										A.debug("Connecting", { transport: this.name, url: r }),
										this.changeState("connecting"),
										!0
									)
								}),
								(t.prototype.close = function () {
									return this.socket ? (this.socket.close(), !0) : !1
								}),
								(t.prototype.send = function (n) {
									return this.state === "open"
										? (j.defer(() => {
												this.socket && this.socket.send(n)
											}),
											!0)
										: !1
								}),
								(t.prototype.ping = function () {
									this.state === "open" &&
										this.supportsPing() &&
										this.socket.ping()
								}),
								(t.prototype.onOpen = function () {
									this.hooks.beforeOpen &&
										this.hooks.beforeOpen(
											this.socket,
											this.hooks.urls.getPath(this.key, this.options),
										),
										this.changeState("open"),
										(this.socket.onopen = void 0)
								}),
								(t.prototype.onError = function (n) {
									this.emit("error", { type: "WebSocketError", error: n }),
										this.timeline.error(
											this.buildTimelineMessage({ error: n.toString() }),
										)
								}),
								(t.prototype.onClose = function (n) {
									n
										? this.changeState("closed", {
												code: n.code,
												reason: n.reason,
												wasClean: n.wasClean,
											})
										: this.changeState("closed"),
										this.unbindListeners(),
										(this.socket = void 0)
								}),
								(t.prototype.onMessage = function (n) {
									this.emit("message", n)
								}),
								(t.prototype.onActivity = function () {
									this.emit("activity")
								}),
								(t.prototype.bindListeners = function () {

									;(this.socket.onopen = () => {
										this.onOpen()
									}),
										(this.socket.onerror = (r) => {
											this.onError(r)
										}),
										(this.socket.onclose = (r) => {
											this.onClose(r)
										}),
										(this.socket.onmessage = (r) => {
											this.onMessage(r)
										}),
										this.supportsPing() &&
											(this.socket.onactivity = () => {
												this.onActivity()
											})
								}),
								(t.prototype.unbindListeners = function () {
									this.socket &&
										((this.socket.onopen = void 0),
										(this.socket.onerror = void 0),
										(this.socket.onclose = void 0),
										(this.socket.onmessage = void 0),
										this.supportsPing() && (this.socket.onactivity = void 0))
								}),
								(t.prototype.changeState = function (n, r) {
									;(this.state = n),
										this.timeline.info(
											this.buildTimelineMessage({ state: n, params: r }),
										),
										this.emit(n, r)
								}),
								(t.prototype.buildTimelineMessage = function (n) {
									return U({ cid: this.id }, n)
								}),
								t
							)
						})(V),
						un = cn,
						hn = (() => {
							function e(t) {
								this.hooks = t
							}
							return (
								(e.prototype.isSupported = function (t) {
									return this.hooks.isSupported(t)
								}),
								(e.prototype.createConnection = function (t, n, r, i) {
									return new un(this.hooks, t, n, r, i)
								}),
								e
							)
						})(),
						tt = hn,
						ln = new tt({
							urls: tn,
							handlesActivityChecks: !1,
							supportsPing: !1,
							isInitialized: () => !!m.getWebSocketAPI(),
							isSupported: () => !!m.getWebSocketAPI(),
							getSocket: (e) => m.createWebSocket(e),
						}),
						Ft = {
							urls: en,
							handlesActivityChecks: !1,
							supportsPing: !0,
							isInitialized: () => !0,
						},
						Bt = U(
							{ getSocket: (e) => m.HTTPFactory.createStreamingSocket(e) },
							Ft,
						),
						Xt = U(
							{ getSocket: (e) => m.HTTPFactory.createPollingSocket(e) },
							Ft,
						),
						Jt = { isSupported: () => m.isXHRSupported() },
						fn = new tt(U({}, Bt, Jt)),
						pn = new tt(U({}, Xt, Jt)),
						dn = { ws: ln, xhr_streaming: fn, xhr_polling: pn },
						ht = dn,
						vn = new tt({
							file: "sockjs",
							urls: nn,
							handlesActivityChecks: !0,
							supportsPing: !1,
							isSupported: () => !0,
							isInitialized: () => window.SockJS !== void 0,
							getSocket: (e, t) =>
								new window.SockJS(e, null, {
									js_path: S.getPath("sockjs", { useTLS: t.useTLS }),
									ignore_null_origin: t.ignoreNullOrigin,
								}),
							beforeOpen: (e, t) => {
								e.send(JSON.stringify({ path: t }))
							},
						}),
						Wt = {
							isSupported: (e) => {
								var t = m.isXDRSupported(e.useTLS)
								return t
							},
						},
						yn = new tt(U({}, Bt, Wt)),
						gn = new tt(U({}, Xt, Wt))
					;(ht.xdr_streaming = yn), (ht.xdr_polling = gn), (ht.sockjs = vn)
					var _n = ht,
						bn = (() => {
							var e = (t, n) => (
								(e =
									Object.setPrototypeOf ||
									({ __proto__: [] } instanceof Array &&
										((r, i) => {
											r.__proto__ = i
										})) ||
									((r, i) => {
										for (var o in i) Object.hasOwn(i, o) && (r[o] = i[o])
									})),
								e(t, n)
							)
							return (t, n) => {
								e(t, n)
								function r() {
									this.constructor = t
								}
								t.prototype =
									n === null
										? Object.create(n)
										: ((r.prototype = n.prototype), new r())
							}
						})(),
						mn = ((e) => {
							bn(t, e)
							function t() {
								var n = e.call(this) || this,
									r = n
								return (
									window.addEventListener !== void 0 &&
										(window.addEventListener(
											"online",
											() => {
												r.emit("online")
											},
											!1,
										),
										window.addEventListener(
											"offline",
											() => {
												r.emit("offline")
											},
											!1,
										)),
									n
								)
							}
							return (
								(t.prototype.isOnline = () =>
									window.navigator.onLine === void 0
										? !0
										: window.navigator.onLine),
								t
							)
						})(V),
						wn = new mn(),
						kn = (() => {
							function e(t, n, r) {
								;(this.manager = t),
									(this.transport = n),
									(this.minPingDelay = r.minPingDelay),
									(this.maxPingDelay = r.maxPingDelay),
									(this.pingDelay = void 0)
							}
							return (
								(e.prototype.createConnection = function (t, n, r, i) {
									i = U({}, i, { activityTimeout: this.pingDelay })
									var u = this.transport.createConnection(t, n, r, i),
										p = null,
										_ = () => {
											u.unbind("open", _), u.bind("closed", g), (p = j.now())
										},
										g = (k) => {
											if (
												(u.unbind("closed", g),
												k.code === 1002 || k.code === 1003)
											)
												this.manager.reportDeath()
											else if (!k.wasClean && p) {
												var E = j.now() - p
												E < 2 * this.maxPingDelay &&
													(this.manager.reportDeath(),
													(this.pingDelay = Math.max(E / 2, this.minPingDelay)))
											}
										}
									return u.bind("open", _), u
								}),
								(e.prototype.isSupported = function (t) {
									return this.manager.isAlive() && this.transport.isSupported(t)
								}),
								e
							)
						})(),
						Sn = kn,
						Vt = {
							decodeMessage: (e) => {
								try {
									var t = JSON.parse(e.data),
										n = t.data
									if (typeof n == "string")
										try {
											n = JSON.parse(t.data)
										} catch {}
									var r = { event: t.event, channel: t.channel, data: n }
									return t.user_id && (r.user_id = t.user_id), r
								} catch (i) {
									throw { type: "MessageParseError", error: i, data: e.data }
								}
							},
							encodeMessage: (e) => JSON.stringify(e),
							processHandshake: function (e) {
								var t = Vt.decodeMessage(e)
								if (t.event === "pusher:connection_established") {
									if (!t.data.activity_timeout)
										throw "No activity timeout specified in handshake"
									return {
										action: "connected",
										id: t.data.socket_id,
										activityTimeout: t.data.activity_timeout * 1e3,
									}
								} else {
									if (t.event === "pusher:error")
										return {
											action: this.getCloseAction(t.data),
											error: this.getCloseError(t.data),
										}
									throw "Invalid handshake"
								}
							},
							getCloseAction: (e) =>
								e.code < 4e3
									? e.code >= 1002 && e.code <= 1004
										? "backoff"
										: null
									: e.code === 4e3
										? "tls_only"
										: e.code < 4100
											? "refused"
											: e.code < 4200
												? "backoff"
												: e.code < 4300
													? "retry"
													: "refused",
							getCloseError: (e) =>
								e.code !== 1e3 && e.code !== 1001
									? {
											type: "PusherError",
											data: { code: e.code, message: e.reason || e.message },
										}
									: null,
						},
						K = Vt,
						Cn = (() => {
							var e = (t, n) => (
								(e =
									Object.setPrototypeOf ||
									({ __proto__: [] } instanceof Array &&
										((r, i) => {
											r.__proto__ = i
										})) ||
									((r, i) => {
										for (var o in i) Object.hasOwn(i, o) && (r[o] = i[o])
									})),
								e(t, n)
							)
							return (t, n) => {
								e(t, n)
								function r() {
									this.constructor = t
								}
								t.prototype =
									n === null
										? Object.create(n)
										: ((r.prototype = n.prototype), new r())
							}
						})(),
						Tn = ((e) => {
							Cn(t, e)
							function t(n, r) {
								var i = e.call(this) || this
								return (
									(i.id = n),
									(i.transport = r),
									(i.activityTimeout = r.activityTimeout),
									i.bindListeners(),
									i
								)
							}
							return (
								(t.prototype.handlesActivityChecks = function () {
									return this.transport.handlesActivityChecks()
								}),
								(t.prototype.send = function (n) {
									return this.transport.send(n)
								}),
								(t.prototype.send_event = function (n, r, i) {
									var o = { event: n, data: r }
									return (
										i && (o.channel = i),
										A.debug("Event sent", o),
										this.send(K.encodeMessage(o))
									)
								}),
								(t.prototype.ping = function () {
									this.transport.supportsPing()
										? this.transport.ping()
										: this.send_event("pusher:ping", {})
								}),
								(t.prototype.close = function () {
									this.transport.close()
								}),
								(t.prototype.bindListeners = function () {
									var r = {
											message: (o) => {
												var u
												try {
													u = K.decodeMessage(o)
												} catch (p) {
													this.emit("error", {
														type: "MessageParseError",
														error: p,
														data: o.data,
													})
												}
												if (u !== void 0) {
													switch ((A.debug("Event recd", u), u.event)) {
														case "pusher:error":
															this.emit("error", {
																type: "PusherError",
																data: u.data,
															})
															break
														case "pusher:ping":
															this.emit("ping")
															break
														case "pusher:pong":
															this.emit("pong")
															break
													}
													this.emit("message", u)
												}
											},
											activity: () => {
												this.emit("activity")
											},
											error: (o) => {
												this.emit("error", o)
											},
											closed: (o) => {
												i(),
													o && o.code && this.handleCloseEvent(o),
													(this.transport = null),
													this.emit("closed")
											},
										},
										i = () => {
											W(r, (o, u) => {
												this.transport.unbind(u, o)
											})
										}
									W(r, (o, u) => {
										this.transport.bind(u, o)
									})
								}),
								(t.prototype.handleCloseEvent = function (n) {
									var r = K.getCloseAction(n),
										i = K.getCloseError(n)
									i && this.emit("error", i),
										r && this.emit(r, { action: r, error: i })
								}),
								t
							)
						})(V),
						Pn = Tn,
						xn = (() => {
							function e(t, n) {
								;(this.transport = t), (this.callback = n), this.bindListeners()
							}
							return (
								(e.prototype.close = function () {
									this.unbindListeners(), this.transport.close()
								}),
								(e.prototype.bindListeners = function () {

									;(this.onMessage = (n) => {
										this.unbindListeners()
										var r
										try {
											r = K.processHandshake(n)
										} catch (i) {
											this.finish("error", { error: i }), this.transport.close()
											return
										}
										r.action === "connected"
											? this.finish("connected", {
													connection: new Pn(r.id, this.transport),
													activityTimeout: r.activityTimeout,
												})
											: (this.finish(r.action, { error: r.error }),
												this.transport.close())
									}),
										(this.onClosed = (n) => {
											this.unbindListeners()
											var r = K.getCloseAction(n) || "backoff",
												i = K.getCloseError(n)
											this.finish(r, { error: i })
										}),
										this.transport.bind("message", this.onMessage),
										this.transport.bind("closed", this.onClosed)
								}),
								(e.prototype.unbindListeners = function () {
									this.transport.unbind("message", this.onMessage),
										this.transport.unbind("closed", this.onClosed)
								}),
								(e.prototype.finish = function (t, n) {
									this.callback(U({ transport: this.transport, action: t }, n))
								}),
								e
							)
						})(),
						On = xn,
						An = (() => {
							function e(t, n) {
								;(this.timeline = t), (this.options = n || {})
							}
							return (
								(e.prototype.send = function (t, n) {
									this.timeline.isEmpty() ||
										this.timeline.send(m.TimelineTransport.getAgent(this, t), n)
								}),
								e
							)
						})(),
						En = An,
						Ln = (() => {
							var e = (t, n) => (
								(e =
									Object.setPrototypeOf ||
									({ __proto__: [] } instanceof Array &&
										((r, i) => {
											r.__proto__ = i
										})) ||
									((r, i) => {
										for (var o in i) Object.hasOwn(i, o) && (r[o] = i[o])
									})),
								e(t, n)
							)
							return (t, n) => {
								e(t, n)
								function r() {
									this.constructor = t
								}
								t.prototype =
									n === null
										? Object.create(n)
										: ((r.prototype = n.prototype), new r())
							}
						})(),
						Rn = ((e) => {
							Ln(t, e)
							function t(n, r) {
								var i =
									e.call(this, (o, u) => {
										A.debug("No callbacks on " + n + " for " + o)
									}) || this
								return (
									(i.name = n),
									(i.pusher = r),
									(i.subscribed = !1),
									(i.subscriptionPending = !1),
									(i.subscriptionCancelled = !1),
									i
								)
							}
							return (
								(t.prototype.authorize = (n, r) => r(null, { auth: "" })),
								(t.prototype.trigger = function (n, r) {
									if (n.indexOf("client-") !== 0)
										throw new w(
											"Event '" + n + "' does not start with 'client-'",
										)
									if (!this.subscribed) {
										var i = b.buildLogSuffix("triggeringClientEvents")
										A.warn(
											"Client event triggered before channel 'subscription_succeeded' event . " +
												i,
										)
									}
									return this.pusher.send_event(n, r, this.name)
								}),
								(t.prototype.disconnect = function () {
									;(this.subscribed = !1), (this.subscriptionPending = !1)
								}),
								(t.prototype.handleEvent = function (n) {
									var r = n.event,
										i = n.data
									if (r === "pusher_internal:subscription_succeeded")
										this.handleSubscriptionSucceededEvent(n)
									else if (r === "pusher_internal:subscription_count")
										this.handleSubscriptionCountEvent(n)
									else if (r.indexOf("pusher_internal:") !== 0) {
										var o = {}
										this.emit(r, i, o)
									}
								}),
								(t.prototype.handleSubscriptionSucceededEvent = function (n) {
									;(this.subscriptionPending = !1),
										(this.subscribed = !0),
										this.subscriptionCancelled
											? this.pusher.unsubscribe(this.name)
											: this.emit("pusher:subscription_succeeded", n.data)
								}),
								(t.prototype.handleSubscriptionCountEvent = function (n) {
									n.data.subscription_count &&
										(this.subscriptionCount = n.data.subscription_count),
										this.emit("pusher:subscription_count", n.data)
								}),
								(t.prototype.subscribe = function () {
									this.subscribed ||
										((this.subscriptionPending = !0),
										(this.subscriptionCancelled = !1),
										this.authorize(this.pusher.connection.socket_id, (r, i) => {
											r
												? ((this.subscriptionPending = !1),
													A.error(r.toString()),
													this.emit(
														"pusher:subscription_error",
														Object.assign(
															{},
															{ type: "AuthError", error: r.message },
															r instanceof B ? { status: r.status } : {},
														),
													))
												: this.pusher.send_event("pusher:subscribe", {
														auth: i.auth,
														channel_data: i.channel_data,
														channel: this.name,
													})
										}))
								}),
								(t.prototype.unsubscribe = function () {
									;(this.subscribed = !1),
										this.pusher.send_event("pusher:unsubscribe", {
											channel: this.name,
										})
								}),
								(t.prototype.cancelSubscription = function () {
									this.subscriptionCancelled = !0
								}),
								(t.prototype.reinstateSubscription = function () {
									this.subscriptionCancelled = !1
								}),
								t
							)
						})(V),
						bt = Rn,
						In = (() => {
							var e = (t, n) => (
								(e =
									Object.setPrototypeOf ||
									({ __proto__: [] } instanceof Array &&
										((r, i) => {
											r.__proto__ = i
										})) ||
									((r, i) => {
										for (var o in i) Object.hasOwn(i, o) && (r[o] = i[o])
									})),
								e(t, n)
							)
							return (t, n) => {
								e(t, n)
								function r() {
									this.constructor = t
								}
								t.prototype =
									n === null
										? Object.create(n)
										: ((r.prototype = n.prototype), new r())
							}
						})(),
						jn = ((e) => {
							In(t, e)
							function t() {
								return (e !== null && e.apply(this, arguments)) || this
							}
							return (
								(t.prototype.authorize = function (n, r) {
									return this.pusher.config.channelAuthorizer(
										{ channelName: this.name, socketId: n },
										r,
									)
								}),
								t
							)
						})(bt),
						mt = jn,
						Nn = (() => {
							function e() {
								this.reset()
							}
							return (
								(e.prototype.get = function (t) {
									return Object.hasOwn(this.members, t)
										? { id: t, info: this.members[t] }
										: null
								}),
								(e.prototype.each = function (t) {
									W(this.members, (r, i) => {
										t(this.get(i))
									})
								}),
								(e.prototype.setMyID = function (t) {
									this.myID = t
								}),
								(e.prototype.onSubscription = function (t) {
									;(this.members = t.presence.hash),
										(this.count = t.presence.count),
										(this.me = this.get(this.myID))
								}),
								(e.prototype.addMember = function (t) {
									return (
										this.get(t.user_id) === null && this.count++,
										(this.members[t.user_id] = t.user_info),
										this.get(t.user_id)
									)
								}),
								(e.prototype.removeMember = function (t) {
									var n = this.get(t.user_id)
									return n && (delete this.members[t.user_id], this.count--), n
								}),
								(e.prototype.reset = function () {
									;(this.members = {}),
										(this.count = 0),
										(this.myID = null),
										(this.me = null)
								}),
								e
							)
						})(),
						qn = Nn,
						Un = (() => {
							var e = (t, n) => (
								(e =
									Object.setPrototypeOf ||
									({ __proto__: [] } instanceof Array &&
										((r, i) => {
											r.__proto__ = i
										})) ||
									((r, i) => {
										for (var o in i) Object.hasOwn(i, o) && (r[o] = i[o])
									})),
								e(t, n)
							)
							return (t, n) => {
								e(t, n)
								function r() {
									this.constructor = t
								}
								t.prototype =
									n === null
										? Object.create(n)
										: ((r.prototype = n.prototype), new r())
							}
						})(),
						Dn = (e, t, n, r) => {
							function i(o) {
								return o instanceof n
									? o
									: new n((u) => {
											u(o)
										})
							}
							return new (n || (n = Promise))((o, u) => {
								function p(k) {
									try {
										g(r.next(k))
									} catch (E) {
										u(E)
									}
								}
								function _(k) {
									try {
										g(r.throw(k))
									} catch (E) {
										u(E)
									}
								}
								function g(k) {
									k.done ? o(k.value) : i(k.value).then(p, _)
								}
								g((r = r.apply(e, t || [])).next())
							})
						},
						Hn = (e, t) => {
							var n = {
									label: 0,
									sent: () => {
										if (o[0] & 1) throw o[1]
										return o[1]
									},
									trys: [],
									ops: [],
								},
								r,
								i,
								o,
								u
							return (
								(u = { next: p(0), throw: p(1), return: p(2) }),
								typeof Symbol == "function" &&
									(u[Symbol.iterator] = function () {
										return this
									}),
								u
							)
							function p(g) {
								return (k) => _([g, k])
							}
							function _(g) {
								if (r) throw new TypeError("Generator is already executing.")
								for (; n; )
									try {
										if (
											((r = 1),
											i &&
												(o =
													g[0] & 2
														? i.return
														: g[0]
															? i.throw || ((o = i.return) && o.call(i), 0)
															: i.next) &&
												!(o = o.call(i, g[1])).done)
										)
											return o
										switch (((i = 0), o && (g = [g[0] & 2, o.value]), g[0])) {
											case 0:
											case 1:
												o = g
												break
											case 4:
												return n.label++, { value: g[1], done: !1 }
											case 5:
												n.label++, (i = g[1]), (g = [0])
												continue
											case 7:
												;(g = n.ops.pop()), n.trys.pop()
												continue
											default:
												if (
													((o = n.trys),
													!(o = o.length > 0 && o[o.length - 1]) &&
														(g[0] === 6 || g[0] === 2))
												) {
													n = 0
													continue
												}
												if (
													g[0] === 3 &&
													(!o || (g[1] > o[0] && g[1] < o[3]))
												) {
													n.label = g[1]
													break
												}
												if (g[0] === 6 && n.label < o[1]) {
													;(n.label = o[1]), (o = g)
													break
												}
												if (o && n.label < o[2]) {
													;(n.label = o[2]), n.ops.push(g)
													break
												}
												o[2] && n.ops.pop(), n.trys.pop()
												continue
										}
										g = t.call(e, n)
									} catch (k) {
										;(g = [6, k]), (i = 0)
									} finally {
										r = o = 0
									}
								if (g[0] & 5) throw g[1]
								return { value: g[0] ? g[1] : void 0, done: !0 }
							}
						},
						Mn = ((e) => {
							Un(t, e)
							function t(n, r) {
								var i = e.call(this, n, r) || this
								return (i.members = new qn()), i
							}
							return (
								(t.prototype.authorize = function (n, r) {
									e.prototype.authorize.call(this, n, (o, u) =>
										Dn(this, void 0, void 0, function () {
											var p, _
											return Hn(this, function (g) {
												switch (g.label) {
													case 0:
														return o
															? [3, 3]
															: ((u = u),
																u.channel_data == null
																	? [3, 1]
																	: ((p = JSON.parse(u.channel_data)),
																		this.members.setMyID(p.user_id),
																		[3, 3]))
													case 1:
														return [4, this.pusher.user.signinDonePromise]
													case 2:
														if ((g.sent(), this.pusher.user.user_data != null))
															this.members.setMyID(
																this.pusher.user.user_data.id,
															)
														else
															return (
																(_ = b.buildLogSuffix("authorizationEndpoint")),
																A.error(
																	"Invalid auth response for channel '" +
																		this.name +
																		"', " +
																		("expected 'channel_data' field. " +
																			_ +
																			", ") +
																		"or the user should be signed in.",
																),
																r("Invalid auth response"),
																[2]
															)
														g.label = 3
													case 3:
														return r(o, u), [2]
												}
											})
										}),
									)
								}),
								(t.prototype.handleEvent = function (n) {
									var r = n.event
									if (r.indexOf("pusher_internal:") === 0)
										this.handleInternalEvent(n)
									else {
										var i = n.data,
											o = {}
										n.user_id && (o.user_id = n.user_id), this.emit(r, i, o)
									}
								}),
								(t.prototype.handleInternalEvent = function (n) {
									var r = n.event,
										i = n.data
									switch (r) {
										case "pusher_internal:subscription_succeeded":
											this.handleSubscriptionSucceededEvent(n)
											break
										case "pusher_internal:subscription_count":
											this.handleSubscriptionCountEvent(n)
											break
										case "pusher_internal:member_added": {
											var o = this.members.addMember(i)
											this.emit("pusher:member_added", o)
											break
										}
										case "pusher_internal:member_removed": {
											var u = this.members.removeMember(i)
											u && this.emit("pusher:member_removed", u)
											break
										}
									}
								}),
								(t.prototype.handleSubscriptionSucceededEvent = function (n) {
									;(this.subscriptionPending = !1),
										(this.subscribed = !0),
										this.subscriptionCancelled
											? this.pusher.unsubscribe(this.name)
											: (this.members.onSubscription(n.data),
												this.emit(
													"pusher:subscription_succeeded",
													this.members,
												))
								}),
								(t.prototype.disconnect = function () {
									this.members.reset(), e.prototype.disconnect.call(this)
								}),
								t
							)
						})(mt),
						zn = Mn,
						Fn = a(1),
						wt = a(0),
						Bn = (() => {
							var e = (t, n) => (
								(e =
									Object.setPrototypeOf ||
									({ __proto__: [] } instanceof Array &&
										((r, i) => {
											r.__proto__ = i
										})) ||
									((r, i) => {
										for (var o in i) Object.hasOwn(i, o) && (r[o] = i[o])
									})),
								e(t, n)
							)
							return (t, n) => {
								e(t, n)
								function r() {
									this.constructor = t
								}
								t.prototype =
									n === null
										? Object.create(n)
										: ((r.prototype = n.prototype), new r())
							}
						})(),
						Xn = ((e) => {
							Bn(t, e)
							function t(n, r, i) {
								var o = e.call(this, n, r) || this
								return (o.key = null), (o.nacl = i), o
							}
							return (
								(t.prototype.authorize = function (n, r) {
									e.prototype.authorize.call(this, n, (o, u) => {
										if (o) {
											r(o, u)
											return
										}
										var p = u.shared_secret
										if (!p) {
											r(
												new Error(
													"No shared_secret key in auth payload for encrypted channel: " +
														this.name,
												),
												null,
											)
											return
										}
										;(this.key = Object(wt.decode)(p)),
											delete u.shared_secret,
											r(null, u)
									})
								}),
								(t.prototype.trigger = (n, r) => {
									throw new J(
										"Client events are not currently supported for encrypted channels",
									)
								}),
								(t.prototype.handleEvent = function (n) {
									var r = n.event,
										i = n.data
									if (
										r.indexOf("pusher_internal:") === 0 ||
										r.indexOf("pusher:") === 0
									) {
										e.prototype.handleEvent.call(this, n)
										return
									}
									this.handleEncryptedEvent(r, i)
								}),
								(t.prototype.handleEncryptedEvent = function (n, r) {
									if (!this.key) {
										A.debug(
											"Received encrypted event before key has been retrieved from the authEndpoint",
										)
										return
									}
									if (!r.ciphertext || !r.nonce) {
										A.error(
											"Unexpected format for encrypted event, expected object with `ciphertext` and `nonce` fields, got: " +
												r,
										)
										return
									}
									var o = Object(wt.decode)(r.ciphertext)
									if (o.length < this.nacl.secretbox.overheadLength) {
										A.error(
											"Expected encrypted event ciphertext length to be " +
												this.nacl.secretbox.overheadLength +
												", got: " +
												o.length,
										)
										return
									}
									var u = Object(wt.decode)(r.nonce)
									if (u.length < this.nacl.secretbox.nonceLength) {
										A.error(
											"Expected encrypted event nonce length to be " +
												this.nacl.secretbox.nonceLength +
												", got: " +
												u.length,
										)
										return
									}
									var p = this.nacl.secretbox.open(o, u, this.key)
									if (p === null) {
										A.debug(
											"Failed to decrypt an event, probably because it was encrypted with a different key. Fetching a new key from the authEndpoint...",
										),
											this.authorize(
												this.pusher.connection.socket_id,
												(_, g) => {
													if (_) {
														A.error(
															"Failed to make a request to the authEndpoint: " +
																g +
																". Unable to fetch new key, so dropping encrypted event",
														)
														return
													}
													if (
														((p = this.nacl.secretbox.open(o, u, this.key)),
														p === null)
													) {
														A.error(
															"Failed to decrypt event with new key. Dropping encrypted event",
														)
														return
													}
													this.emit(n, this.getDataToEmit(p))
												},
											)
										return
									}
									this.emit(n, this.getDataToEmit(p))
								}),
								(t.prototype.getDataToEmit = (n) => {
									var r = Object(Fn.decode)(n)
									try {
										return JSON.parse(r)
									} catch {
										return r
									}
								}),
								t
							)
						})(mt),
						Jn = Xn,
						Wn = (() => {
							var e = (t, n) => (
								(e =
									Object.setPrototypeOf ||
									({ __proto__: [] } instanceof Array &&
										((r, i) => {
											r.__proto__ = i
										})) ||
									((r, i) => {
										for (var o in i) Object.hasOwn(i, o) && (r[o] = i[o])
									})),
								e(t, n)
							)
							return (t, n) => {
								e(t, n)
								function r() {
									this.constructor = t
								}
								t.prototype =
									n === null
										? Object.create(n)
										: ((r.prototype = n.prototype), new r())
							}
						})(),
						Vn = ((e) => {
							Wn(t, e)
							function t(n, r) {
								var i = e.call(this) || this
								;(i.state = "initialized"),
									(i.connection = null),
									(i.key = n),
									(i.options = r),
									(i.timeline = i.options.timeline),
									(i.usingTLS = i.options.useTLS),
									(i.errorCallbacks = i.buildErrorCallbacks()),
									(i.connectionCallbacks = i.buildConnectionCallbacks(
										i.errorCallbacks,
									)),
									(i.handshakeCallbacks = i.buildHandshakeCallbacks(
										i.errorCallbacks,
									))
								var o = m.getNetwork()
								return (
									o.bind("online", () => {
										i.timeline.info({ netinfo: "online" }),
											(i.state === "connecting" || i.state === "unavailable") &&
												i.retryIn(0)
									}),
									o.bind("offline", () => {
										i.timeline.info({ netinfo: "offline" }),
											i.connection && i.sendActivityCheck()
									}),
									i.updateStrategy(),
									i
								)
							}
							return (
								(t.prototype.connect = function () {
									if (!(this.connection || this.runner)) {
										if (!this.strategy.isSupported()) {
											this.updateState("failed")
											return
										}
										this.updateState("connecting"),
											this.startConnecting(),
											this.setUnavailableTimer()
									}
								}),
								(t.prototype.send = function (n) {
									return this.connection ? this.connection.send(n) : !1
								}),
								(t.prototype.send_event = function (n, r, i) {
									return this.connection
										? this.connection.send_event(n, r, i)
										: !1
								}),
								(t.prototype.disconnect = function () {
									this.disconnectInternally(), this.updateState("disconnected")
								}),
								(t.prototype.isUsingTLS = function () {
									return this.usingTLS
								}),
								(t.prototype.startConnecting = function () {
									var r = (i, o) => {
										i
											? (this.runner = this.strategy.connect(0, r))
											: o.action === "error"
												? (this.emit("error", {
														type: "HandshakeError",
														error: o.error,
													}),
													this.timeline.error({ handshakeError: o.error }))
												: (this.abortConnecting(),
													this.handshakeCallbacks[o.action](o))
									}
									this.runner = this.strategy.connect(0, r)
								}),
								(t.prototype.abortConnecting = function () {
									this.runner && (this.runner.abort(), (this.runner = null))
								}),
								(t.prototype.disconnectInternally = function () {
									if (
										(this.abortConnecting(),
										this.clearRetryTimer(),
										this.clearUnavailableTimer(),
										this.connection)
									) {
										var n = this.abandonConnection()
										n.close()
									}
								}),
								(t.prototype.updateStrategy = function () {
									this.strategy = this.options.getStrategy({
										key: this.key,
										timeline: this.timeline,
										useTLS: this.usingTLS,
									})
								}),
								(t.prototype.retryIn = function (n) {
									this.timeline.info({ action: "retry", delay: n }),
										n > 0 && this.emit("connecting_in", Math.round(n / 1e3)),
										(this.retryTimer = new Q(n || 0, () => {
											this.disconnectInternally(), this.connect()
										}))
								}),
								(t.prototype.clearRetryTimer = function () {
									this.retryTimer &&
										(this.retryTimer.ensureAborted(), (this.retryTimer = null))
								}),
								(t.prototype.setUnavailableTimer = function () {
									this.unavailableTimer = new Q(
										this.options.unavailableTimeout,
										() => {
											this.updateState("unavailable")
										},
									)
								}),
								(t.prototype.clearUnavailableTimer = function () {
									this.unavailableTimer && this.unavailableTimer.ensureAborted()
								}),
								(t.prototype.sendActivityCheck = function () {
									this.stopActivityCheck(),
										this.connection.ping(),
										(this.activityTimer = new Q(
											this.options.pongTimeout,
											() => {
												this.timeline.error({
													pong_timed_out: this.options.pongTimeout,
												}),
													this.retryIn(0)
											},
										))
								}),
								(t.prototype.resetActivityCheck = function () {
									this.stopActivityCheck(),
										this.connection &&
											!this.connection.handlesActivityChecks() &&
											(this.activityTimer = new Q(this.activityTimeout, () => {
												this.sendActivityCheck()
											}))
								}),
								(t.prototype.stopActivityCheck = function () {
									this.activityTimer && this.activityTimer.ensureAborted()
								}),
								(t.prototype.buildConnectionCallbacks = function (n) {
									return U({}, n, {
										message: (i) => {
											this.resetActivityCheck(), this.emit("message", i)
										},
										ping: () => {
											this.send_event("pusher:pong", {})
										},
										activity: () => {
											this.resetActivityCheck()
										},
										error: (i) => {
											this.emit("error", i)
										},
										closed: () => {
											this.abandonConnection(),
												this.shouldRetry() && this.retryIn(1e3)
										},
									})
								}),
								(t.prototype.buildHandshakeCallbacks = function (n) {
									return U({}, n, {
										connected: (i) => {
											;(this.activityTimeout = Math.min(
												this.options.activityTimeout,
												i.activityTimeout,
												i.connection.activityTimeout || 1 / 0,
											)),
												this.clearUnavailableTimer(),
												this.setConnection(i.connection),
												(this.socket_id = this.connection.id),
												this.updateState("connected", {
													socket_id: this.socket_id,
												})
										},
									})
								}),
								(t.prototype.buildErrorCallbacks = function () {
									var r = (i) => (o) => {
										o.error &&
											this.emit("error", {
												type: "WebSocketError",
												error: o.error,
											}),
											i(o)
									}
									return {
										tls_only: r(() => {
											;(this.usingTLS = !0),
												this.updateStrategy(),
												this.retryIn(0)
										}),
										refused: r(() => {
											this.disconnect()
										}),
										backoff: r(() => {
											this.retryIn(1e3)
										}),
										retry: r(() => {
											this.retryIn(0)
										}),
									}
								}),
								(t.prototype.setConnection = function (n) {
									this.connection = n
									for (var r in this.connectionCallbacks)
										this.connection.bind(r, this.connectionCallbacks[r])
									this.resetActivityCheck()
								}),
								(t.prototype.abandonConnection = function () {
									if (this.connection) {
										this.stopActivityCheck()
										for (var n in this.connectionCallbacks)
											this.connection.unbind(n, this.connectionCallbacks[n])
										var r = this.connection
										return (this.connection = null), r
									}
								}),
								(t.prototype.updateState = function (n, r) {
									var i = this.state
									if (((this.state = n), i !== n)) {
										var o = n
										o === "connected" &&
											(o += " with new socket ID " + r.socket_id),
											A.debug("State changed", i + " -> " + o),
											this.timeline.info({ state: n, params: r }),
											this.emit("state_change", { previous: i, current: n }),
											this.emit(n, r)
									}
								}),
								(t.prototype.shouldRetry = function () {
									return (
										this.state === "connecting" || this.state === "connected"
									)
								}),
								t
							)
						})(V),
						Gn = Vn,
						Qn = (() => {
							function e() {
								this.channels = {}
							}
							return (
								(e.prototype.add = function (t, n) {
									return (
										this.channels[t] || (this.channels[t] = Yn(t, n)),
										this.channels[t]
									)
								}),
								(e.prototype.all = function () {
									return Ue(this.channels)
								}),
								(e.prototype.find = function (t) {
									return this.channels[t]
								}),
								(e.prototype.remove = function (t) {
									var n = this.channels[t]
									return delete this.channels[t], n
								}),
								(e.prototype.disconnect = function () {
									W(this.channels, (t) => {
										t.disconnect()
									})
								}),
								e
							)
						})(),
						Kn = Qn
					function Yn(e, t) {
						if (e.indexOf("private-encrypted-") === 0) {
							if (t.config.nacl)
								return G.createEncryptedChannel(e, t, t.config.nacl)
							var n =
									"Tried to subscribe to a private-encrypted- channel but no nacl implementation available",
								r = b.buildLogSuffix("encryptedChannelSupport")
							throw new J(n + ". " + r)
						} else {
							if (e.indexOf("private-") === 0)
								return G.createPrivateChannel(e, t)
							if (e.indexOf("presence-") === 0)
								return G.createPresenceChannel(e, t)
							if (e.indexOf("#") === 0)
								throw new O('Cannot create a channel with name "' + e + '".')
							return G.createChannel(e, t)
						}
					}
					var $n = {
							createChannels: () => new Kn(),
							createConnectionManager: (e, t) => new Gn(e, t),
							createChannel: (e, t) => new bt(e, t),
							createPrivateChannel: (e, t) => new mt(e, t),
							createPresenceChannel: (e, t) => new zn(e, t),
							createEncryptedChannel: (e, t, n) => new Jn(e, t, n),
							createTimelineSender: (e, t) => new En(e, t),
							createHandshake: (e, t) => new On(e, t),
							createAssistantToTheTransportManager: (e, t, n) =>
								new Sn(e, t, n),
						},
						G = $n,
						Zn = (() => {
							function e(t) {
								;(this.options = t || {}),
									(this.livesLeft = this.options.lives || 1 / 0)
							}
							return (
								(e.prototype.getAssistant = function (t) {
									return G.createAssistantToTheTransportManager(this, t, {
										minPingDelay: this.options.minPingDelay,
										maxPingDelay: this.options.maxPingDelay,
									})
								}),
								(e.prototype.isAlive = function () {
									return this.livesLeft > 0
								}),
								(e.prototype.reportDeath = function () {
									this.livesLeft -= 1
								}),
								e
							)
						})(),
						Gt = Zn,
						tr = (() => {
							function e(t, n) {
								;(this.strategies = t),
									(this.loop = !!n.loop),
									(this.failFast = !!n.failFast),
									(this.timeout = n.timeout),
									(this.timeoutLimit = n.timeoutLimit)
							}
							return (
								(e.prototype.isSupported = function () {
									return zt(this.strategies, j.method("isSupported"))
								}),
								(e.prototype.connect = function (t, n) {
									var i = this.strategies,
										o = 0,
										u = this.timeout,
										p = null,
										_ = (g, k) => {
											k
												? n(null, k)
												: ((o = o + 1),
													this.loop && (o = o % i.length),
													o < i.length
														? (u &&
																((u = u * 2),
																this.timeoutLimit &&
																	(u = Math.min(u, this.timeoutLimit))),
															(p = this.tryStrategy(
																i[o],
																t,
																{ timeout: u, failFast: this.failFast },
																_,
															)))
														: n(!0))
										}
									return (
										(p = this.tryStrategy(
											i[o],
											t,
											{ timeout: u, failFast: this.failFast },
											_,
										)),
										{
											abort: () => {
												p.abort()
											},
											forceMinPriority: (g) => {
												;(t = g), p && p.forceMinPriority(g)
											},
										}
									)
								}),
								(e.prototype.tryStrategy = (t, n, r, i) => {
									var o = null,
										u = null
									return (
										r.timeout > 0 &&
											(o = new Q(r.timeout, () => {
												u.abort(), i(!0)
											})),
										(u = t.connect(n, (p, _) => {
											;(p && o && o.isRunning() && !r.failFast) ||
												(o && o.ensureAborted(), i(p, _))
										})),
										{
											abort: () => {
												o && o.ensureAborted(), u.abort()
											},
											forceMinPriority: (p) => {
												u.forceMinPriority(p)
											},
										}
									)
								}),
								e
							)
						})(),
						Y = tr,
						er = (() => {
							function e(t) {
								this.strategies = t
							}
							return (
								(e.prototype.isSupported = function () {
									return zt(this.strategies, j.method("isSupported"))
								}),
								(e.prototype.connect = function (t, n) {
									return nr(this.strategies, t, (r, i) => (o, u) => {
										if (((i[r].error = o), o)) {
											rr(i) && n(!0)
											return
										}
										rt(i, (p) => {
											p.forceMinPriority(u.transport.priority)
										}),
											n(null, u)
									})
								}),
								e
							)
						})(),
						kt = er
					function nr(e, t, n) {
						var r = Dt(e, (i, o, u, p) => i.connect(t, n(o, p)))
						return {
							abort: () => {
								rt(r, ir)
							},
							forceMinPriority: (i) => {
								rt(r, (o) => {
									o.forceMinPriority(i)
								})
							},
						}
					}
					function rr(e) {
						return Me(e, (t) => !!t.error)
					}
					function ir(e) {
						!e.error && !e.aborted && (e.abort(), (e.aborted = !0))
					}
					var or = (() => {
							function e(t, n, r) {
								;(this.strategy = t),
									(this.transports = n),
									(this.ttl = r.ttl || 1800 * 1e3),
									(this.usingTLS = r.useTLS),
									(this.timeline = r.timeline)
							}
							return (
								(e.prototype.isSupported = function () {
									return this.strategy.isSupported()
								}),
								(e.prototype.connect = function (t, n) {
									var r = this.usingTLS,
										i = ar(r),
										o = [this.strategy]
									if (i && i.timestamp + this.ttl >= j.now()) {
										var u = this.transports[i.transport]
										u &&
											(this.timeline.info({
												cached: !0,
												transport: i.transport,
												latency: i.latency,
											}),
											o.push(
												new Y([u], {
													timeout: i.latency * 2 + 1e3,
													failFast: !0,
												}),
											))
									}
									var p = j.now(),
										_ = o.pop().connect(t, function g(k, E) {
											k
												? (Qt(r),
													o.length > 0
														? ((p = j.now()), (_ = o.pop().connect(t, g)))
														: n(k))
												: (cr(r, E.transport.name, j.now() - p), n(null, E))
										})
									return {
										abort: () => {
											_.abort()
										},
										forceMinPriority: (g) => {
											;(t = g), _ && _.forceMinPriority(g)
										},
									}
								}),
								e
							)
						})(),
						sr = or
					function St(e) {
						return "pusherTransport" + (e ? "TLS" : "NonTLS")
					}
					function ar(e) {
						var t = m.getLocalStorage()
						if (t)
							try {
								var n = t[St(e)]
								if (n) return JSON.parse(n)
							} catch {
								Qt(e)
							}
						return null
					}
					function cr(e, t, n) {
						var r = m.getLocalStorage()
						if (r)
							try {
								r[St(e)] = ut({ timestamp: j.now(), transport: t, latency: n })
							} catch {}
					}
					function Qt(e) {
						var t = m.getLocalStorage()
						if (t)
							try {
								delete t[St(e)]
							} catch {}
					}
					var ur = (() => {
							function e(t, n) {
								var r = n.delay
								;(this.strategy = t), (this.options = { delay: r })
							}
							return (
								(e.prototype.isSupported = function () {
									return this.strategy.isSupported()
								}),
								(e.prototype.connect = function (t, n) {
									var r = this.strategy,
										i,
										o = new Q(this.options.delay, () => {
											i = r.connect(t, n)
										})
									return {
										abort: () => {
											o.ensureAborted(), i && i.abort()
										},
										forceMinPriority: (u) => {
											;(t = u), i && i.forceMinPriority(u)
										},
									}
								}),
								e
							)
						})(),
						lt = ur,
						hr = (() => {
							function e(t, n, r) {
								;(this.test = t), (this.trueBranch = n), (this.falseBranch = r)
							}
							return (
								(e.prototype.isSupported = function () {
									var t = this.test() ? this.trueBranch : this.falseBranch
									return t.isSupported()
								}),
								(e.prototype.connect = function (t, n) {
									var r = this.test() ? this.trueBranch : this.falseBranch
									return r.connect(t, n)
								}),
								e
							)
						})(),
						it = hr,
						lr = (() => {
							function e(t) {
								this.strategy = t
							}
							return (
								(e.prototype.isSupported = function () {
									return this.strategy.isSupported()
								}),
								(e.prototype.connect = function (t, n) {
									var r = this.strategy.connect(t, (i, o) => {
										o && r.abort(), n(i, o)
									})
									return r
								}),
								e
							)
						})(),
						fr = lr
					function ot(e) {
						return () => e.isSupported()
					}
					var pr = (e, t, n) => {
							var r = {}
							function i(ce, mi, wi, ki, Si) {
								var ue = n(e, ce, mi, wi, ki, Si)
								return (r[ce] = ue), ue
							}
							var o = Object.assign({}, t, {
									hostNonTLS: e.wsHost + ":" + e.wsPort,
									hostTLS: e.wsHost + ":" + e.wssPort,
									httpPath: e.wsPath,
								}),
								u = Object.assign({}, o, { useTLS: !0 }),
								p = Object.assign({}, t, {
									hostNonTLS: e.httpHost + ":" + e.httpPort,
									hostTLS: e.httpHost + ":" + e.httpsPort,
									httpPath: e.httpPath,
								}),
								_ = { loop: !0, timeout: 15e3, timeoutLimit: 6e4 },
								g = new Gt({
									lives: 2,
									minPingDelay: 1e4,
									maxPingDelay: e.activityTimeout,
								}),
								k = new Gt({
									lives: 2,
									minPingDelay: 1e4,
									maxPingDelay: e.activityTimeout,
								}),
								E = i("ws", "ws", 3, o, g),
								X = i("wss", "ws", 3, u, g),
								vi = i("sockjs", "sockjs", 1, p),
								ne = i("xhr_streaming", "xhr_streaming", 1, p, k),
								yi = i("xdr_streaming", "xdr_streaming", 1, p, k),
								re = i("xhr_polling", "xhr_polling", 1, p),
								gi = i("xdr_polling", "xdr_polling", 1, p),
								ie = new Y([E], _),
								_i = new Y([X], _),
								bi = new Y([vi], _),
								oe = new Y([new it(ot(ne), ne, yi)], _),
								se = new Y([new it(ot(re), re, gi)], _),
								ae = new Y(
									[
										new it(
											ot(oe),
											new kt([oe, new lt(se, { delay: 4e3 })]),
											se,
										),
									],
									_,
								),
								xt = new it(ot(ae), ae, bi),
								Ot
							return (
								t.useTLS
									? (Ot = new kt([ie, new lt(xt, { delay: 2e3 })]))
									: (Ot = new kt([
											ie,
											new lt(_i, { delay: 2e3 }),
											new lt(xt, { delay: 5e3 }),
										])),
								new sr(new fr(new it(ot(E), Ot, xt)), r, {
									ttl: 18e5,
									timeline: t.timeline,
									useTLS: t.useTLS,
								})
							)
						},
						dr = pr,
						vr = function () {
							this.timeline.info(
								this.buildTimelineMessage({
									transport: this.name + (this.options.useTLS ? "s" : ""),
								}),
							),
								this.hooks.isInitialized()
									? this.changeState("initialized")
									: this.hooks.file
										? (this.changeState("initializing"),
											S.load(
												this.hooks.file,
												{ useTLS: this.options.useTLS },
												(t, n) => {
													this.hooks.isInitialized()
														? (this.changeState("initialized"), n(!0))
														: (t && this.onError(t), this.onClose(), n(!1))
												},
											))
										: this.onClose()
						},
						yr = {
							getRequest: (e) => {
								var t = new window.XDomainRequest()
								return (
									(t.ontimeout = () => {
										e.emit("error", new I()), e.close()
									}),
									(t.onerror = (n) => {
										e.emit("error", n), e.close()
									}),
									(t.onprogress = () => {
										t.responseText &&
											t.responseText.length > 0 &&
											e.onChunk(200, t.responseText)
									}),
									(t.onload = () => {
										t.responseText &&
											t.responseText.length > 0 &&
											e.onChunk(200, t.responseText),
											e.emit("finished", 200),
											e.close()
									}),
									t
								)
							},
							abortRequest: (e) => {
								;(e.ontimeout = e.onerror = e.onprogress = e.onload = null),
									e.abort()
							},
						},
						gr = yr,
						_r = (() => {
							var e = (t, n) => (
								(e =
									Object.setPrototypeOf ||
									({ __proto__: [] } instanceof Array &&
										((r, i) => {
											r.__proto__ = i
										})) ||
									((r, i) => {
										for (var o in i) Object.hasOwn(i, o) && (r[o] = i[o])
									})),
								e(t, n)
							)
							return (t, n) => {
								e(t, n)
								function r() {
									this.constructor = t
								}
								t.prototype =
									n === null
										? Object.create(n)
										: ((r.prototype = n.prototype), new r())
							}
						})(),
						br = 256 * 1024,
						mr = ((e) => {
							_r(t, e)
							function t(n, r, i) {
								var o = e.call(this) || this
								return (o.hooks = n), (o.method = r), (o.url = i), o
							}
							return (
								(t.prototype.start = function (n) {

									;(this.position = 0),
										(this.xhr = this.hooks.getRequest(this)),
										(this.unloader = () => {
											this.close()
										}),
										m.addUnloadListener(this.unloader),
										this.xhr.open(this.method, this.url, !0),
										this.xhr.setRequestHeader &&
											this.xhr.setRequestHeader(
												"Content-Type",
												"application/json",
											),
										this.xhr.send(n)
								}),
								(t.prototype.close = function () {
									this.unloader &&
										(m.removeUnloadListener(this.unloader),
										(this.unloader = null)),
										this.xhr &&
											(this.hooks.abortRequest(this.xhr), (this.xhr = null))
								}),
								(t.prototype.onChunk = function (n, r) {
									for (;;) {
										var i = this.advanceBuffer(r)
										if (i) this.emit("chunk", { status: n, data: i })
										else break
									}
									this.isBufferTooLong(r) && this.emit("buffer_too_long")
								}),
								(t.prototype.advanceBuffer = function (n) {
									var r = n.slice(this.position),
										i = r.indexOf(`
`)
									return i !== -1
										? ((this.position += i + 1), r.slice(0, i))
										: null
								}),
								(t.prototype.isBufferTooLong = function (n) {
									return this.position === n.length && n.length > br
								}),
								t
							)
						})(V),
						wr = mr,
						Ct
					;((e) => {
						;(e[(e.CONNECTING = 0)] = "CONNECTING"),
							(e[(e.OPEN = 1)] = "OPEN"),
							(e[(e.CLOSED = 3)] = "CLOSED")
					})(Ct || (Ct = {}))
					var $ = Ct,
						kr = 1,
						Sr = (() => {
							function e(t, n) {
								;(this.hooks = t),
									(this.session = Yt(1e3) + "/" + xr(8)),
									(this.location = Cr(n)),
									(this.readyState = $.CONNECTING),
									this.openStream()
							}
							return (
								(e.prototype.send = function (t) {
									return this.sendRaw(JSON.stringify([t]))
								}),
								(e.prototype.ping = function () {
									this.hooks.sendHeartbeat(this)
								}),
								(e.prototype.close = function (t, n) {
									this.onClose(t, n, !0)
								}),
								(e.prototype.sendRaw = function (t) {
									if (this.readyState === $.OPEN)
										try {
											return (
												m
													.createSocketRequest(
														"POST",
														Kt(Tr(this.location, this.session)),
													)
													.start(t),
												!0
											)
										} catch {
											return !1
										}
									else return !1
								}),
								(e.prototype.reconnect = function () {
									this.closeStream(), this.openStream()
								}),
								(e.prototype.onClose = function (t, n, r) {
									this.closeStream(),
										(this.readyState = $.CLOSED),
										this.onclose &&
											this.onclose({ code: t, reason: n, wasClean: r })
								}),
								(e.prototype.onChunk = function (t) {
									if (t.status === 200) {
										this.readyState === $.OPEN && this.onActivity()
										var n,
											r = t.data.slice(0, 1)
										switch (r) {
											case "o":
												;(n = JSON.parse(t.data.slice(1) || "{}")),
													this.onOpen(n)
												break
											case "a":
												n = JSON.parse(t.data.slice(1) || "[]")
												for (var i = 0; i < n.length; i++) this.onEvent(n[i])
												break
											case "m":
												;(n = JSON.parse(t.data.slice(1) || "null")),
													this.onEvent(n)
												break
											case "h":
												this.hooks.onHeartbeat(this)
												break
											case "c":
												;(n = JSON.parse(t.data.slice(1) || "[]")),
													this.onClose(n[0], n[1], !0)
												break
										}
									}
								}),
								(e.prototype.onOpen = function (t) {
									this.readyState === $.CONNECTING
										? (t &&
												t.hostname &&
												(this.location.base = Pr(
													this.location.base,
													t.hostname,
												)),
											(this.readyState = $.OPEN),
											this.onopen && this.onopen())
										: this.onClose(1006, "Server lost session", !0)
								}),
								(e.prototype.onEvent = function (t) {
									this.readyState === $.OPEN &&
										this.onmessage &&
										this.onmessage({ data: t })
								}),
								(e.prototype.onActivity = function () {
									this.onactivity && this.onactivity()
								}),
								(e.prototype.onError = function (t) {
									this.onerror && this.onerror(t)
								}),
								(e.prototype.openStream = function () {

									;(this.stream = m.createSocketRequest(
										"POST",
										Kt(this.hooks.getReceiveURL(this.location, this.session)),
									)),
										this.stream.bind("chunk", (n) => {
											this.onChunk(n)
										}),
										this.stream.bind("finished", (n) => {
											this.hooks.onFinished(this, n)
										}),
										this.stream.bind("buffer_too_long", () => {
											this.reconnect()
										})
									try {
										this.stream.start()
									} catch (n) {
										j.defer(() => {
											this.onError(n),
												this.onClose(1006, "Could not start streaming", !1)
										})
									}
								}),
								(e.prototype.closeStream = function () {
									this.stream &&
										(this.stream.unbind_all(),
										this.stream.close(),
										(this.stream = null))
								}),
								e
							)
						})()
					function Cr(e) {
						var t = /([^?]*)\/*(\??.*)/.exec(e)
						return { base: t[1], queryString: t[2] }
					}
					function Tr(e, t) {
						return e.base + "/" + t + "/xhr_send"
					}
					function Kt(e) {
						var t = e.indexOf("?") === -1 ? "?" : "&"
						return e + t + "t=" + +new Date() + "&n=" + kr++
					}
					function Pr(e, t) {
						var n = /(https?:\/\/)([^/:]+)((\/|:)?.*)/.exec(e)
						return n[1] + t + n[3]
					}
					function Yt(e) {
						return m.randomInt(e)
					}
					function xr(e) {
						for (var t = [], n = 0; n < e; n++) t.push(Yt(32).toString(32))
						return t.join("")
					}
					var Or = Sr,
						Ar = {
							getReceiveURL: (e, t) =>
								e.base + "/" + t + "/xhr_streaming" + e.queryString,
							onHeartbeat: (e) => {
								e.sendRaw("[]")
							},
							sendHeartbeat: (e) => {
								e.sendRaw("[]")
							},
							onFinished: (e, t) => {
								e.onClose(1006, "Connection interrupted (" + t + ")", !1)
							},
						},
						Er = Ar,
						Lr = {
							getReceiveURL: (e, t) =>
								e.base + "/" + t + "/xhr" + e.queryString,
							onHeartbeat: () => {},
							sendHeartbeat: (e) => {
								e.sendRaw("[]")
							},
							onFinished: (e, t) => {
								t === 200
									? e.reconnect()
									: e.onClose(1006, "Connection interrupted (" + t + ")", !1)
							},
						},
						Rr = Lr,
						Ir = {
							getRequest: (e) => {
								var t = m.getXHRAPI(),
									n = new t()
								return (
									(n.onreadystatechange = n.onprogress =
										() => {
											switch (n.readyState) {
												case 3:
													n.responseText &&
														n.responseText.length > 0 &&
														e.onChunk(n.status, n.responseText)
													break
												case 4:
													n.responseText &&
														n.responseText.length > 0 &&
														e.onChunk(n.status, n.responseText),
														e.emit("finished", n.status),
														e.close()
													break
											}
										}),
									n
								)
							},
							abortRequest: (e) => {
								;(e.onreadystatechange = null), e.abort()
							},
						},
						jr = Ir,
						Nr = {
							createStreamingSocket: function (e) {
								return this.createSocket(Er, e)
							},
							createPollingSocket: function (e) {
								return this.createSocket(Rr, e)
							},
							createSocket: (e, t) => new Or(e, t),
							createXHR: function (e, t) {
								return this.createRequest(jr, e, t)
							},
							createRequest: (e, t, n) => new wr(e, t, n),
						},
						$t = Nr
					$t.createXDR = function (e, t) {
						return this.createRequest(gr, e, t)
					}
					var qr = $t,
						Ur = {
							nextAuthCallbackID: 1,
							auth_callbacks: {},
							ScriptReceivers: s,
							DependenciesReceivers: T,
							getDefaultStrategy: dr,
							Transports: _n,
							transportConnectionInitializer: vr,
							HTTPFactory: qr,
							TimelineTransport: Ze,
							getXHRAPI: () => window.XMLHttpRequest,
							getWebSocketAPI: () => window.WebSocket || window.MozWebSocket,
							setup: function (e) {
								window.Pusher = e
								var n = () => {
									this.onDocumentBody(e.ready)
								}
								window.JSON ? n() : S.load("json2", {}, n)
							},
							getDocument: () => document,
							getProtocol: function () {
								return this.getDocument().location.protocol
							},
							getAuthorizers: () => ({ ajax: Se, jsonp: We }),
							onDocumentBody: function (e) {
								document.body
									? e()
									: setTimeout(() => {
											this.onDocumentBody(e)
										}, 0)
							},
							createJSONPRequest: (e, t) => new Ke(e, t),
							createScriptRequest: (e) => new Ge(e),
							getLocalStorage: () => {
								try {
									return window.localStorage
								} catch {
									return
								}
							},
							createXHR: function () {
								return this.getXHRAPI()
									? this.createXMLHttpRequest()
									: this.createMicrosoftXHR()
							},
							createXMLHttpRequest: function () {
								var e = this.getXHRAPI()
								return new e()
							},
							createMicrosoftXHR: () => new ActiveXObject("Microsoft.XMLHTTP"),
							getNetwork: () => wn,
							createWebSocket: function (e) {
								var t = this.getWebSocketAPI()
								return new t(e)
							},
							createSocketRequest: function (e, t) {
								if (this.isXHRSupported())
									return this.HTTPFactory.createXHR(e, t)
								if (this.isXDRSupported(t.indexOf("https:") === 0))
									return this.HTTPFactory.createXDR(e, t)
								throw "Cross-origin HTTP requests are not supported"
							},
							isXHRSupported: function () {
								var e = this.getXHRAPI()
								return !!e && new e().withCredentials !== void 0
							},
							isXDRSupported: function (e) {
								var t = e ? "https:" : "http:",
									n = this.getProtocol()
								return !!window.XDomainRequest && n === t
							},
							addUnloadListener: (e) => {
								window.addEventListener !== void 0
									? window.addEventListener("unload", e, !1)
									: window.attachEvent !== void 0 &&
										window.attachEvent("onunload", e)
							},
							removeUnloadListener: (e) => {
								window.addEventListener !== void 0
									? window.removeEventListener("unload", e, !1)
									: window.detachEvent !== void 0 &&
										window.detachEvent("onunload", e)
							},
							randomInt: (e) => {
								var t = () => {
									var n = window.crypto || window.msCrypto,
										r = n.getRandomValues(new Uint32Array(1))[0]
									return r / 2 ** 32
								}
								return Math.floor(t() * e)
							},
						},
						m = Ur,
						Tt
					;((e) => {
						;(e[(e.ERROR = 3)] = "ERROR"),
							(e[(e.INFO = 6)] = "INFO"),
							(e[(e.DEBUG = 7)] = "DEBUG")
					})(Tt || (Tt = {}))
					var ft = Tt,
						Dr = (() => {
							function e(t, n, r) {
								;(this.key = t),
									(this.session = n),
									(this.events = []),
									(this.options = r || {}),
									(this.sent = 0),
									(this.uniqueID = 0)
							}
							return (
								(e.prototype.log = function (t, n) {
									t <= this.options.level &&
										(this.events.push(U({}, n, { timestamp: j.now() })),
										this.options.limit &&
											this.events.length > this.options.limit &&
											this.events.shift())
								}),
								(e.prototype.error = function (t) {
									this.log(ft.ERROR, t)
								}),
								(e.prototype.info = function (t) {
									this.log(ft.INFO, t)
								}),
								(e.prototype.debug = function (t) {
									this.log(ft.DEBUG, t)
								}),
								(e.prototype.isEmpty = function () {
									return this.events.length === 0
								}),
								(e.prototype.send = function (t, n) {
									var i = U(
										{
											session: this.session,
											bundle: this.sent + 1,
											key: this.key,
											lib: "js",
											version: this.options.version,
											cluster: this.options.cluster,
											features: this.options.features,
											timeline: this.events,
										},
										this.options.params,
									)
									return (
										(this.events = []),
										t(i, (o, u) => {
											o || this.sent++, n && n(o, u)
										}),
										!0
									)
								}),
								(e.prototype.generateUniqueID = function () {
									return this.uniqueID++, this.uniqueID
								}),
								e
							)
						})(),
						Hr = Dr,
						Mr = (() => {
							function e(t, n, r, i) {
								;(this.name = t),
									(this.priority = n),
									(this.transport = r),
									(this.options = i || {})
							}
							return (
								(e.prototype.isSupported = function () {
									return this.transport.isSupported({
										useTLS: this.options.useTLS,
									})
								}),
								(e.prototype.connect = function (t, n) {
									if (this.isSupported()) {
										if (this.priority < t) return Zt(new q(), n)
									} else return Zt(new z(), n)
									var i = !1,
										o = this.transport.createConnection(
											this.name,
											this.priority,
											this.options.key,
											this.options,
										),
										u = null,
										p = () => {
											o.unbind("initialized", p), o.connect()
										},
										_ = () => {
											u = G.createHandshake(o, (X) => {
												;(i = !0), E(), n(null, X)
											})
										},
										g = (X) => {
											E(), n(X)
										},
										k = () => {
											E()
											var X
											;(X = ut(o)), n(new M(X))
										},
										E = () => {
											o.unbind("initialized", p),
												o.unbind("open", _),
												o.unbind("error", g),
												o.unbind("closed", k)
										}
									return (
										o.bind("initialized", p),
										o.bind("open", _),
										o.bind("error", g),
										o.bind("closed", k),
										o.initialize(),
										{
											abort: () => {
												i || (E(), u ? u.close() : o.close())
											},
											forceMinPriority: (X) => {
												i || (this.priority < X && (u ? u.close() : o.close()))
											},
										}
									)
								}),
								e
							)
						})(),
						zr = Mr
					function Zt(e, t) {
						return (
							j.defer(() => {
								t(e)
							}),
							{ abort: () => {}, forceMinPriority: () => {} }
						)
					}
					var Fr = m.Transports,
						Br = (e, t, n, r, i, o) => {
							var u = Fr[n]
							if (!u) throw new F(n)
							var p =
									(!e.enabledTransports || qt(e.enabledTransports, t) !== -1) &&
									(!e.disabledTransports || qt(e.disabledTransports, t) === -1),
								_
							return (
								p
									? ((i = Object.assign(
											{ ignoreNullOrigin: e.ignoreNullOrigin },
											i,
										)),
										(_ = new zr(t, r, o ? o.getAssistant(u) : u, i)))
									: (_ = Xr),
								_
							)
						},
						Xr = {
							isSupported: () => !1,
							connect: (e, t) => {
								var n = j.defer(() => {
									t(new z())
								})
								return {
									abort: () => {
										n.ensureAborted()
									},
									forceMinPriority: () => {},
								}
							},
						},
						Jr = (e, t) => {
							var n = "socket_id=" + encodeURIComponent(e.socketId)
							for (var r in t.params)
								n +=
									"&" +
									encodeURIComponent(r) +
									"=" +
									encodeURIComponent(t.params[r])
							if (t.paramsProvider != null) {
								var i = t.paramsProvider()
								for (var r in i)
									n +=
										"&" + encodeURIComponent(r) + "=" + encodeURIComponent(i[r])
							}
							return n
						},
						Wr = (e) => {
							if (typeof m.getAuthorizers()[e.transport] > "u")
								throw "'" + e.transport + "' is not a recognized auth transport"
							return (t, n) => {
								var r = Jr(t, e)
								m.getAuthorizers()[e.transport](
									m,
									r,
									e,
									v.UserAuthentication,
									n,
								)
							}
						},
						Vr = Wr,
						Gr = (e, t) => {
							var n = "socket_id=" + encodeURIComponent(e.socketId)
							n += "&channel_name=" + encodeURIComponent(e.channelName)
							for (var r in t.params)
								n +=
									"&" +
									encodeURIComponent(r) +
									"=" +
									encodeURIComponent(t.params[r])
							if (t.paramsProvider != null) {
								var i = t.paramsProvider()
								for (var r in i)
									n +=
										"&" + encodeURIComponent(r) + "=" + encodeURIComponent(i[r])
							}
							return n
						},
						Qr = (e) => {
							if (typeof m.getAuthorizers()[e.transport] > "u")
								throw "'" + e.transport + "' is not a recognized auth transport"
							return (t, n) => {
								var r = Gr(t, e)
								m.getAuthorizers()[e.transport](
									m,
									r,
									e,
									v.ChannelAuthorization,
									n,
								)
							}
						},
						Kr = Qr,
						Yr = (e, t, n) => {
							var r = {
								authTransport: t.transport,
								authEndpoint: t.endpoint,
								auth: { params: t.params, headers: t.headers },
							}
							return (i, o) => {
								var u = e.channel(i.channelName),
									p = n(u, r)
								p.authorize(i.socketId, o)
							}
						},
						et = function () {
							return (
								(et =
									Object.assign ||
									function (e) {
										for (var t, n = 1, r = arguments.length; n < r; n++) {
											t = arguments[n]
											for (var i in t) Object.hasOwn(t, i) && (e[i] = t[i])
										}
										return e
									}),
								et.apply(this, arguments)
							)
						}
					function $r(e, t) {
						var n = {
							activityTimeout: e.activityTimeout || d.activityTimeout,
							cluster: e.cluster || d.cluster,
							httpPath: e.httpPath || d.httpPath,
							httpPort: e.httpPort || d.httpPort,
							httpsPort: e.httpsPort || d.httpsPort,
							pongTimeout: e.pongTimeout || d.pongTimeout,
							statsHost: e.statsHost || d.stats_host,
							unavailableTimeout: e.unavailableTimeout || d.unavailableTimeout,
							wsPath: e.wsPath || d.wsPath,
							wsPort: e.wsPort || d.wsPort,
							wssPort: e.wssPort || d.wssPort,
							enableStats: ni(e),
							httpHost: Zr(e),
							useTLS: ei(e),
							wsHost: ti(e),
							userAuthenticator: ri(e),
							channelAuthorizer: oi(e, t),
						}
						return (
							"disabledTransports" in e &&
								(n.disabledTransports = e.disabledTransports),
							"enabledTransports" in e &&
								(n.enabledTransports = e.enabledTransports),
							"ignoreNullOrigin" in e &&
								(n.ignoreNullOrigin = e.ignoreNullOrigin),
							"timelineParams" in e && (n.timelineParams = e.timelineParams),
							"nacl" in e && (n.nacl = e.nacl),
							n
						)
					}
					function Zr(e) {
						return e.httpHost
							? e.httpHost
							: e.cluster
								? "sockjs-" + e.cluster + ".pusher.com"
								: d.httpHost
					}
					function ti(e) {
						return e.wsHost
							? e.wsHost
							: e.cluster
								? te(e.cluster)
								: te(d.cluster)
					}
					function te(e) {
						return "ws-" + e + ".pusher.com"
					}
					function ei(e) {
						return m.getProtocol() === "https:" ? !0 : e.forceTLS !== !1
					}
					function ni(e) {
						return "enableStats" in e
							? e.enableStats
							: "disableStats" in e
								? !e.disableStats
								: !1
					}
					function ri(e) {
						var t = et(et({}, d.userAuthentication), e.userAuthentication)
						return "customHandler" in t && t.customHandler != null
							? t.customHandler
							: Vr(t)
					}
					function ii(e, t) {
						var n
						return (
							"channelAuthorization" in e
								? (n = et(
										et({}, d.channelAuthorization),
										e.channelAuthorization,
									))
								: ((n = {
										transport: e.authTransport || d.authTransport,
										endpoint: e.authEndpoint || d.authEndpoint,
									}),
									"auth" in e &&
										("params" in e.auth && (n.params = e.auth.params),
										"headers" in e.auth && (n.headers = e.auth.headers)),
									"authorizer" in e &&
										(n.customHandler = Yr(t, n, e.authorizer))),
							n
						)
					}
					function oi(e, t) {
						var n = ii(e, t)
						return "customHandler" in n && n.customHandler != null
							? n.customHandler
							: Kr(n)
					}
					var si = (() => {
							var e = (t, n) => (
								(e =
									Object.setPrototypeOf ||
									({ __proto__: [] } instanceof Array &&
										((r, i) => {
											r.__proto__ = i
										})) ||
									((r, i) => {
										for (var o in i) Object.hasOwn(i, o) && (r[o] = i[o])
									})),
								e(t, n)
							)
							return (t, n) => {
								e(t, n)
								function r() {
									this.constructor = t
								}
								t.prototype =
									n === null
										? Object.create(n)
										: ((r.prototype = n.prototype), new r())
							}
						})(),
						ai = ((e) => {
							si(t, e)
							function t(n) {
								var r =
									e.call(this, (i, o) => {
										A.debug("No callbacks on watchlist events for " + i)
									}) || this
								return (r.pusher = n), r.bindWatchlistInternalEvent(), r
							}
							return (
								(t.prototype.handleEvent = function (n) {
									n.data.events.forEach((i) => {
										this.emit(i.name, i)
									})
								}),
								(t.prototype.bindWatchlistInternalEvent = function () {
									this.pusher.connection.bind("message", (r) => {
										var i = r.event
										i === "pusher_internal:watchlist_events" &&
											this.handleEvent(r)
									})
								}),
								t
							)
						})(V),
						ci = ai
					function ui() {
						var e,
							t,
							n = new Promise((r, i) => {
								;(e = r), (t = i)
							})
						return { promise: n, resolve: e, reject: t }
					}
					var hi = ui,
						li = (() => {
							var e = (t, n) => (
								(e =
									Object.setPrototypeOf ||
									({ __proto__: [] } instanceof Array &&
										((r, i) => {
											r.__proto__ = i
										})) ||
									((r, i) => {
										for (var o in i) Object.hasOwn(i, o) && (r[o] = i[o])
									})),
								e(t, n)
							)
							return (t, n) => {
								e(t, n)
								function r() {
									this.constructor = t
								}
								t.prototype =
									n === null
										? Object.create(n)
										: ((r.prototype = n.prototype), new r())
							}
						})(),
						fi = ((e) => {
							li(t, e)
							function t(n) {
								var r =
									e.call(this, (i, o) => {
										A.debug("No callbacks on user for " + i)
									}) || this
								return (
									(r.signin_requested = !1),
									(r.user_data = null),
									(r.serverToUserChannel = null),
									(r.signinDonePromise = null),
									(r._signinDoneResolve = null),
									(r._onAuthorize = (i, o) => {
										if (i) {
											A.warn("Error during signin: " + i), r._cleanup()
											return
										}
										r.pusher.send_event("pusher:signin", {
											auth: o.auth,
											user_data: o.user_data,
										})
									}),
									(r.pusher = n),
									r.pusher.connection.bind("state_change", (i) => {
										var o = i.previous,
											u = i.current
										o !== "connected" && u === "connected" && r._signin(),
											o === "connected" &&
												u !== "connected" &&
												(r._cleanup(), r._newSigninPromiseIfNeeded())
									}),
									(r.watchlist = new ci(n)),
									r.pusher.connection.bind("message", (i) => {
										var o = i.event
										o === "pusher:signin_success" && r._onSigninSuccess(i.data),
											r.serverToUserChannel &&
												r.serverToUserChannel.name === i.channel &&
												r.serverToUserChannel.handleEvent(i)
									}),
									r
								)
							}
							return (
								(t.prototype.signin = function () {
									this.signin_requested ||
										((this.signin_requested = !0), this._signin())
								}),
								(t.prototype._signin = function () {
									this.signin_requested &&
										(this._newSigninPromiseIfNeeded(),
										this.pusher.connection.state === "connected" &&
											this.pusher.config.userAuthenticator(
												{ socketId: this.pusher.connection.socket_id },
												this._onAuthorize,
											))
								}),
								(t.prototype._onSigninSuccess = function (n) {
									try {
										this.user_data = JSON.parse(n.user_data)
									} catch {
										A.error(
											"Failed parsing user data after signin: " + n.user_data,
										),
											this._cleanup()
										return
									}
									if (
										typeof this.user_data.id != "string" ||
										this.user_data.id === ""
									) {
										A.error(
											"user_data doesn't contain an id. user_data: " +
												this.user_data,
										),
											this._cleanup()
										return
									}
									this._signinDoneResolve(), this._subscribeChannels()
								}),
								(t.prototype._subscribeChannels = function () {
									var r = (i) => {
										i.subscriptionPending && i.subscriptionCancelled
											? i.reinstateSubscription()
											: !i.subscriptionPending &&
												this.pusher.connection.state === "connected" &&
												i.subscribe()
									}
									;(this.serverToUserChannel = new bt(
										"#server-to-user-" + this.user_data.id,
										this.pusher,
									)),
										this.serverToUserChannel.bind_global((i, o) => {
											i.indexOf("pusher_internal:") === 0 ||
												i.indexOf("pusher:") === 0 ||
												this.emit(i, o)
										}),
										r(this.serverToUserChannel)
								}),
								(t.prototype._cleanup = function () {
									;(this.user_data = null),
										this.serverToUserChannel &&
											(this.serverToUserChannel.unbind_all(),
											this.serverToUserChannel.disconnect(),
											(this.serverToUserChannel = null)),
										this.signin_requested && this._signinDoneResolve()
								}),
								(t.prototype._newSigninPromiseIfNeeded = function () {
									if (
										this.signin_requested &&
										!(this.signinDonePromise && !this.signinDonePromise.done)
									) {
										var n = hi(),
											r = n.promise,
											i = n.resolve,
											o = n.reject
										r.done = !1
										var u = () => {
											r.done = !0
										}
										r.then(u).catch(u),
											(this.signinDonePromise = r),
											(this._signinDoneResolve = i)
									}
								}),
								t
							)
						})(V),
						pi = fi,
						ee = (() => {
							function e(t, n) {
								if (
									(di(t),
									(n = n || {}),
									!n.cluster && !(n.wsHost || n.httpHost))
								) {
									var i = b.buildLogSuffix("javascriptQuickStart")
									A.warn(
										"You should always specify a cluster when connecting. " + i,
									)
								}
								"disableStats" in n &&
									A.warn(
										"The disableStats option is deprecated in favor of enableStats",
									),
									(this.key = t),
									(this.config = $r(n, this)),
									(this.channels = G.createChannels()),
									(this.global_emitter = new V()),
									(this.sessionID = m.randomInt(1e9)),
									(this.timeline = new Hr(this.key, this.sessionID, {
										cluster: this.config.cluster,
										features: e.getClientFeatures(),
										params: this.config.timelineParams || {},
										limit: 50,
										level: ft.INFO,
										version: d.VERSION,
									})),
									this.config.enableStats &&
										(this.timelineSender = G.createTimelineSender(
											this.timeline,
											{
												host: this.config.statsHost,
												path: "/timeline/v2/" + m.TimelineTransport.name,
											},
										))
								var o = (u) => m.getDefaultStrategy(this.config, u, Br)
								;(this.connection = G.createConnectionManager(this.key, {
									getStrategy: o,
									timeline: this.timeline,
									activityTimeout: this.config.activityTimeout,
									pongTimeout: this.config.pongTimeout,
									unavailableTimeout: this.config.unavailableTimeout,
									useTLS: !!this.config.useTLS,
								})),
									this.connection.bind("connected", () => {
										this.subscribeAll(),
											this.timelineSender &&
												this.timelineSender.send(this.connection.isUsingTLS())
									}),
									this.connection.bind("message", (u) => {
										var p = u.event,
											_ = p.indexOf("pusher_internal:") === 0
										if (u.channel) {
											var g = this.channel(u.channel)
											g && g.handleEvent(u)
										}
										_ || this.global_emitter.emit(u.event, u.data)
									}),
									this.connection.bind("connecting", () => {
										this.channels.disconnect()
									}),
									this.connection.bind("disconnected", () => {
										this.channels.disconnect()
									}),
									this.connection.bind("error", (u) => {
										A.warn(u)
									}),
									e.instances.push(this),
									this.timeline.info({ instances: e.instances.length }),
									(this.user = new pi(this)),
									e.isReady && this.connect()
							}
							return (
								(e.ready = () => {
									e.isReady = !0
									for (var t = 0, n = e.instances.length; t < n; t++)
										e.instances[t].connect()
								}),
								(e.getClientFeatures = () =>
									Ut(Mt({ ws: m.Transports.ws }, (t) => t.isSupported({})))),
								(e.prototype.channel = function (t) {
									return this.channels.find(t)
								}),
								(e.prototype.allChannels = function () {
									return this.channels.all()
								}),
								(e.prototype.connect = function () {
									if (
										(this.connection.connect(),
										this.timelineSender && !this.timelineSenderTimer)
									) {
										var t = this.connection.isUsingTLS(),
											n = this.timelineSender
										this.timelineSenderTimer = new je(6e4, () => {
											n.send(t)
										})
									}
								}),
								(e.prototype.disconnect = function () {
									this.connection.disconnect(),
										this.timelineSenderTimer &&
											(this.timelineSenderTimer.ensureAborted(),
											(this.timelineSenderTimer = null))
								}),
								(e.prototype.bind = function (t, n, r) {
									return this.global_emitter.bind(t, n, r), this
								}),
								(e.prototype.unbind = function (t, n, r) {
									return this.global_emitter.unbind(t, n, r), this
								}),
								(e.prototype.bind_global = function (t) {
									return this.global_emitter.bind_global(t), this
								}),
								(e.prototype.unbind_global = function (t) {
									return this.global_emitter.unbind_global(t), this
								}),
								(e.prototype.unbind_all = function (t) {
									return this.global_emitter.unbind_all(), this
								}),
								(e.prototype.subscribeAll = function () {
									var t
									for (t in this.channels.channels)
										Object.hasOwn(this.channels.channels, t) &&
											this.subscribe(t)
								}),
								(e.prototype.subscribe = function (t) {
									var n = this.channels.add(t, this)
									return (
										n.subscriptionPending && n.subscriptionCancelled
											? n.reinstateSubscription()
											: !n.subscriptionPending &&
												this.connection.state === "connected" &&
												n.subscribe(),
										n
									)
								}),
								(e.prototype.unsubscribe = function (t) {
									var n = this.channels.find(t)
									n && n.subscriptionPending
										? n.cancelSubscription()
										: ((n = this.channels.remove(t)),
											n && n.subscribed && n.unsubscribe())
								}),
								(e.prototype.send_event = function (t, n, r) {
									return this.connection.send_event(t, n, r)
								}),
								(e.prototype.shouldUseTLS = function () {
									return this.config.useTLS
								}),
								(e.prototype.signin = function () {
									this.user.signin()
								}),
								(e.instances = []),
								(e.isReady = !1),
								(e.logToConsole = !1),
								(e.Runtime = m),
								(e.ScriptReceivers = m.ScriptReceivers),
								(e.DependenciesReceivers = m.DependenciesReceivers),
								(e.auth_callbacks = m.auth_callbacks),
								e
							)
						})(),
						Pt = (h.default = ee)
					function di(e) {
						if (e == null)
							throw "You must pass your app key when you instantiate Pusher."
					}
					m.setup(ee)
				},
			]),
		)
	})
	function st(l) {
		"@babel/helpers - typeof"
		return (
			(st =
				typeof Symbol == "function" && typeof Symbol.iterator == "symbol"
					? (h) => typeof h
					: (h) =>
							h &&
							typeof Symbol == "function" &&
							h.constructor === Symbol &&
							h !== Symbol.prototype
								? "symbol"
								: typeof h),
			st(l)
		)
	}
	function L(l, h) {
		if (!(l instanceof h))
			throw new TypeError("Cannot call a class as a function")
	}
	function le(l, h) {
		for (var a = 0; a < h.length; a++) {
			var c = h[a]
			;(c.enumerable = c.enumerable || !1),
				(c.configurable = !0),
				"value" in c && (c.writable = !0),
				Object.defineProperty(l, c.key, c)
		}
	}
	function R(l, h, a) {
		return (
			h && le(l.prototype, h),
			a && le(l, a),
			Object.defineProperty(l, "prototype", { writable: !1 }),
			l
		)
	}
	function at() {
		return (
			(at =
				Object.assign ||
				function (l) {
					for (var h = 1; h < arguments.length; h++) {
						var a = arguments[h]
						for (var c in a) Object.hasOwn(a, c) && (l[c] = a[c])
					}
					return l
				}),
			at.apply(this, arguments)
		)
	}
	function D(l, h) {
		if (typeof h != "function" && h !== null)
			throw new TypeError("Super expression must either be null or a function")
		;(l.prototype = Object.create(h && h.prototype, {
			constructor: { value: l, writable: !0, configurable: !0 },
		})),
			Object.defineProperty(l, "prototype", { writable: !1 }),
			h && At(l, h)
	}
	function pt(l) {
		return (
			(pt = Object.setPrototypeOf
				? Object.getPrototypeOf
				: (a) => a.__proto__ || Object.getPrototypeOf(a)),
			pt(l)
		)
	}
	function At(l, h) {
		return (
			(At = Object.setPrototypeOf || ((c, s) => ((c.__proto__ = s), c))),
			At(l, h)
		)
	}
	function Ri() {
		if (typeof Reflect > "u" || !Reflect.construct || Reflect.construct.sham)
			return !1
		if (typeof Proxy == "function") return !0
		try {
			return (
				Boolean.prototype.valueOf.call(
					Reflect.construct(Boolean, [], () => {}),
				),
				!0
			)
		} catch {
			return !1
		}
	}
	function Ii(l) {
		if (l === void 0)
			throw new ReferenceError(
				"this hasn't been initialised - super() hasn't been called",
			)
		return l
	}
	function ji(l, h) {
		if (h && (typeof h == "object" || typeof h == "function")) return h
		if (h !== void 0)
			throw new TypeError(
				"Derived constructors may only return object or undefined",
			)
		return Ii(l)
	}
	function H(l) {
		var h = Ri()
		return function () {
			var c = pt(l),
				s
			if (h) {
				var f = pt(this).constructor
				s = Reflect.construct(c, arguments, f)
			} else s = c.apply(this, arguments)
			return ji(this, s)
		}
	}
	var Et = (() => {
			function l() {
				L(this, l)
			}
			return (
				R(l, [
					{
						key: "listenForWhisper",
						value: function (a, c) {
							return this.listen(".client-" + a, c)
						},
					},
					{
						key: "notification",
						value: function (a) {
							return this.listen(
								".Illuminate\\Notifications\\Events\\BroadcastNotificationCreated",
								a,
							)
						},
					},
					{
						key: "stopListeningForWhisper",
						value: function (a, c) {
							return this.stopListening(".client-" + a, c)
						},
					},
				]),
				l
			)
		})(),
		de = (() => {
			function l(h) {
				L(this, l), (this.namespace = h)
			}
			return (
				R(l, [
					{
						key: "format",
						value: function (a) {
							return [".", "\\"].includes(a.charAt(0))
								? a.substring(1)
								: (this.namespace && (a = this.namespace + "." + a),
									a.replace(/\./g, "\\"))
						},
					},
					{
						key: "setNamespace",
						value: function (a) {
							this.namespace = a
						},
					},
				]),
				l
			)
		})()
	function Ni(l) {
		try {
			new l()
		} catch (h) {
			if (h.message.includes("is not a constructor")) return !1
		}
		return !0
	}
	var Lt = ((l) => {
			D(a, l)
			var h = H(a)
			function a(c, s, f) {
				var d
				return (
					L(this, a),
					(d = h.call(this)),
					(d.name = s),
					(d.pusher = c),
					(d.options = f),
					(d.eventFormatter = new de(d.options.namespace)),
					d.subscribe(),
					d
				)
			}
			return (
				R(a, [
					{
						key: "subscribe",
						value: function () {
							this.subscription = this.pusher.subscribe(this.name)
						},
					},
					{
						key: "unsubscribe",
						value: function () {
							this.pusher.unsubscribe(this.name)
						},
					},
					{
						key: "listen",
						value: function (s, f) {
							return this.on(this.eventFormatter.format(s), f), this
						},
					},
					{
						key: "listenToAll",
						value: function (s) {
							return (
								this.subscription.bind_global((d, N) => {
									if (!d.startsWith("pusher:")) {
										var P = this.options.namespace.replace(/\./g, "\\"),
											T = d.startsWith(P) ? d.substring(P.length + 1) : "." + d
										s(T, N)
									}
								}),
								this
							)
						},
					},
					{
						key: "stopListening",
						value: function (s, f) {
							return (
								f
									? this.subscription.unbind(this.eventFormatter.format(s), f)
									: this.subscription.unbind(this.eventFormatter.format(s)),
								this
							)
						},
					},
					{
						key: "stopListeningToAll",
						value: function (s) {
							return (
								s
									? this.subscription.unbind_global(s)
									: this.subscription.unbind_global(),
								this
							)
						},
					},
					{
						key: "subscribed",
						value: function (s) {
							return (
								this.on("pusher:subscription_succeeded", () => {
									s()
								}),
								this
							)
						},
					},
					{
						key: "error",
						value: function (s) {
							return (
								this.on("pusher:subscription_error", (f) => {
									s(f)
								}),
								this
							)
						},
					},
					{
						key: "on",
						value: function (s, f) {
							return this.subscription.bind(s, f), this
						},
					},
				]),
				a
			)
		})(Et),
		ve = ((l) => {
			D(a, l)
			var h = H(a)
			function a() {
				return L(this, a), h.apply(this, arguments)
			}
			return (
				R(a, [
					{
						key: "whisper",
						value: function (s, f) {
							return (
								this.pusher.channels.channels[this.name].trigger(
									"client-".concat(s),
									f,
								),
								this
							)
						},
					},
				]),
				a
			)
		})(Lt),
		qi = ((l) => {
			D(a, l)
			var h = H(a)
			function a() {
				return L(this, a), h.apply(this, arguments)
			}
			return (
				R(a, [
					{
						key: "whisper",
						value: function (s, f) {
							return (
								this.pusher.channels.channels[this.name].trigger(
									"client-".concat(s),
									f,
								),
								this
							)
						},
					},
				]),
				a
			)
		})(Lt),
		Ui = ((l) => {
			D(a, l)
			var h = H(a)
			function a() {
				return L(this, a), h.apply(this, arguments)
			}
			return (
				R(a, [
					{
						key: "here",
						value: function (s) {
							return (
								this.on("pusher:subscription_succeeded", (f) => {
									s(Object.keys(f.members).map((d) => f.members[d]))
								}),
								this
							)
						},
					},
					{
						key: "joining",
						value: function (s) {
							return (
								this.on("pusher:member_added", (f) => {
									s(f.info)
								}),
								this
							)
						},
					},
					{
						key: "whisper",
						value: function (s, f) {
							return (
								this.pusher.channels.channels[this.name].trigger(
									"client-".concat(s),
									f,
								),
								this
							)
						},
					},
					{
						key: "leaving",
						value: function (s) {
							return (
								this.on("pusher:member_removed", (f) => {
									s(f.info)
								}),
								this
							)
						},
					},
				]),
				a
			)
		})(ve),
		ye = ((l) => {
			D(a, l)
			var h = H(a)
			function a(c, s, f) {
				var d
				return (
					L(this, a),
					(d = h.call(this)),
					(d.events = {}),
					(d.listeners = {}),
					(d.name = s),
					(d.socket = c),
					(d.options = f),
					(d.eventFormatter = new de(d.options.namespace)),
					d.subscribe(),
					d
				)
			}
			return (
				R(a, [
					{
						key: "subscribe",
						value: function () {
							this.socket.emit("subscribe", {
								channel: this.name,
								auth: this.options.auth || {},
							})
						},
					},
					{
						key: "unsubscribe",
						value: function () {
							this.unbind(),
								this.socket.emit("unsubscribe", {
									channel: this.name,
									auth: this.options.auth || {},
								})
						},
					},
					{
						key: "listen",
						value: function (s, f) {
							return this.on(this.eventFormatter.format(s), f), this
						},
					},
					{
						key: "stopListening",
						value: function (s, f) {
							return this.unbindEvent(this.eventFormatter.format(s), f), this
						},
					},
					{
						key: "subscribed",
						value: function (s) {
							return (
								this.on("connect", (f) => {
									s(f)
								}),
								this
							)
						},
					},
					{
						key: "error",
						value: function (s) {
							return this
						},
					},
					{
						key: "on",
						value: function (s, f) {
							return (
								(this.listeners[s] = this.listeners[s] || []),
								this.events[s] ||
									((this.events[s] = (N, P) => {
										this.name === N &&
											this.listeners[s] &&
											this.listeners[s].forEach((T) => T(P))
									}),
									this.socket.on(s, this.events[s])),
								this.listeners[s].push(f),
								this
							)
						},
					},
					{
						key: "unbind",
						value: function () {
							Object.keys(this.events).forEach((f) => {
								this.unbindEvent(f)
							})
						},
					},
					{
						key: "unbindEvent",
						value: function (s, f) {
							;(this.listeners[s] = this.listeners[s] || []),
								f &&
									(this.listeners[s] = this.listeners[s].filter(
										(d) => d !== f,
									)),
								(!f || this.listeners[s].length === 0) &&
									(this.events[s] &&
										(this.socket.removeListener(s, this.events[s]),
										delete this.events[s]),
									delete this.listeners[s])
						},
					},
				]),
				a
			)
		})(Et),
		ge = ((l) => {
			D(a, l)
			var h = H(a)
			function a() {
				return L(this, a), h.apply(this, arguments)
			}
			return (
				R(a, [
					{
						key: "whisper",
						value: function (s, f) {
							return (
								this.socket.emit("client event", {
									channel: this.name,
									event: "client-".concat(s),
									data: f,
								}),
								this
							)
						},
					},
				]),
				a
			)
		})(ye),
		Di = ((l) => {
			D(a, l)
			var h = H(a)
			function a() {
				return L(this, a), h.apply(this, arguments)
			}
			return (
				R(a, [
					{
						key: "here",
						value: function (s) {
							return (
								this.on("presence:subscribed", (f) => {
									s(f.map((d) => d.user_info))
								}),
								this
							)
						},
					},
					{
						key: "joining",
						value: function (s) {
							return this.on("presence:joining", (f) => s(f.user_info)), this
						},
					},
					{
						key: "whisper",
						value: function (s, f) {
							return (
								this.socket.emit("client event", {
									channel: this.name,
									event: "client-".concat(s),
									data: f,
								}),
								this
							)
						},
					},
					{
						key: "leaving",
						value: function (s) {
							return this.on("presence:leaving", (f) => s(f.user_info)), this
						},
					},
				]),
				a
			)
		})(ge),
		dt = ((l) => {
			D(a, l)
			var h = H(a)
			function a() {
				return L(this, a), h.apply(this, arguments)
			}
			return (
				R(a, [
					{ key: "subscribe", value: () => {} },
					{ key: "unsubscribe", value: () => {} },
					{
						key: "listen",
						value: function (s, f) {
							return this
						},
					},
					{
						key: "listenToAll",
						value: function (s) {
							return this
						},
					},
					{
						key: "stopListening",
						value: function (s, f) {
							return this
						},
					},
					{
						key: "subscribed",
						value: function (s) {
							return this
						},
					},
					{
						key: "error",
						value: function (s) {
							return this
						},
					},
					{
						key: "on",
						value: function (s, f) {
							return this
						},
					},
				]),
				a
			)
		})(Et),
		_e = ((l) => {
			D(a, l)
			var h = H(a)
			function a() {
				return L(this, a), h.apply(this, arguments)
			}
			return (
				R(a, [
					{
						key: "whisper",
						value: function (s, f) {
							return this
						},
					},
				]),
				a
			)
		})(dt),
		Hi = ((l) => {
			D(a, l)
			var h = H(a)
			function a() {
				return L(this, a), h.apply(this, arguments)
			}
			return (
				R(a, [
					{
						key: "whisper",
						value: function (s, f) {
							return this
						},
					},
				]),
				a
			)
		})(dt),
		Mi = ((l) => {
			D(a, l)
			var h = H(a)
			function a() {
				return L(this, a), h.apply(this, arguments)
			}
			return (
				R(a, [
					{
						key: "here",
						value: function (s) {
							return this
						},
					},
					{
						key: "joining",
						value: function (s) {
							return this
						},
					},
					{
						key: "whisper",
						value: function (s, f) {
							return this
						},
					},
					{
						key: "leaving",
						value: function (s) {
							return this
						},
					},
				]),
				a
			)
		})(_e),
		Rt = (() => {
			function l(h) {
				L(this, l),
					(this._defaultOptions = {
						auth: { headers: {} },
						authEndpoint: "/broadcasting/auth",
						userAuthentication: {
							endpoint: "/broadcasting/user-auth",
							headers: {},
						},
						broadcaster: "pusher",
						csrfToken: null,
						bearerToken: null,
						host: null,
						key: null,
						namespace: "App.Events",
					}),
					this.setOptions(h),
					this.connect()
			}
			return (
				R(l, [
					{
						key: "setOptions",
						value: function (a) {
							this.options = at(this._defaultOptions, a)
							var c = this.csrfToken()
							return (
								c &&
									((this.options.auth.headers["X-CSRF-TOKEN"] = c),
									(this.options.userAuthentication.headers["X-CSRF-TOKEN"] =
										c)),
								(c = this.options.bearerToken),
								c &&
									((this.options.auth.headers.Authorization = "Bearer " + c),
									(this.options.userAuthentication.headers.Authorization =
										"Bearer " + c)),
								a
							)
						},
					},
					{
						key: "csrfToken",
						value: function () {
							var a
							return typeof window < "u" &&
								window.Laravel &&
								window.Laravel.csrfToken
								? window.Laravel.csrfToken
								: this.options.csrfToken
									? this.options.csrfToken
									: typeof document < "u" &&
											typeof document.querySelector == "function" &&
											(a = document.querySelector('meta[name="csrf-token"]'))
										? a.getAttribute("content")
										: null
						},
					},
				]),
				l
			)
		})(),
		fe = ((l) => {
			D(a, l)
			var h = H(a)
			function a() {
				var c
				return L(this, a), (c = h.apply(this, arguments)), (c.channels = {}), c
			}
			return (
				R(a, [
					{
						key: "connect",
						value: function () {
							typeof this.options.client < "u"
								? (this.pusher = this.options.client)
								: this.options.Pusher
									? (this.pusher = new this.options.Pusher(
											this.options.key,
											this.options,
										))
									: (this.pusher = new Pusher(this.options.key, this.options))
						},
					},
					{
						key: "signin",
						value: function () {
							this.pusher.signin()
						},
					},
					{
						key: "listen",
						value: function (s, f, d) {
							return this.channel(s).listen(f, d)
						},
					},
					{
						key: "channel",
						value: function (s) {
							return (
								this.channels[s] ||
									(this.channels[s] = new Lt(this.pusher, s, this.options)),
								this.channels[s]
							)
						},
					},
					{
						key: "privateChannel",
						value: function (s) {
							return (
								this.channels["private-" + s] ||
									(this.channels["private-" + s] = new ve(
										this.pusher,
										"private-" + s,
										this.options,
									)),
								this.channels["private-" + s]
							)
						},
					},
					{
						key: "encryptedPrivateChannel",
						value: function (s) {
							return (
								this.channels["private-encrypted-" + s] ||
									(this.channels["private-encrypted-" + s] = new qi(
										this.pusher,
										"private-encrypted-" + s,
										this.options,
									)),
								this.channels["private-encrypted-" + s]
							)
						},
					},
					{
						key: "presenceChannel",
						value: function (s) {
							return (
								this.channels["presence-" + s] ||
									(this.channels["presence-" + s] = new Ui(
										this.pusher,
										"presence-" + s,
										this.options,
									)),
								this.channels["presence-" + s]
							)
						},
					},
					{
						key: "leave",
						value: function (s) {
							var d = [
								s,
								"private-" + s,
								"private-encrypted-" + s,
								"presence-" + s,
							]
							d.forEach((N, P) => {
								this.leaveChannel(N)
							})
						},
					},
					{
						key: "leaveChannel",
						value: function (s) {
							this.channels[s] &&
								(this.channels[s].unsubscribe(), delete this.channels[s])
						},
					},
					{
						key: "socketId",
						value: function () {
							return this.pusher.connection.socket_id
						},
					},
					{
						key: "disconnect",
						value: function () {
							this.pusher.disconnect()
						},
					},
				]),
				a
			)
		})(Rt),
		pe = ((l) => {
			D(a, l)
			var h = H(a)
			function a() {
				var c
				return L(this, a), (c = h.apply(this, arguments)), (c.channels = {}), c
			}
			return (
				R(a, [
					{
						key: "connect",
						value: function () {
							var f = this.getSocketIO()
							return (
								(this.socket = f(this.options.host, this.options)),
								this.socket.on("reconnect", () => {
									Object.values(this.channels).forEach((d) => {
										d.subscribe()
									})
								}),
								this.socket
							)
						},
					},
					{
						key: "getSocketIO",
						value: function () {
							if (typeof this.options.client < "u") return this.options.client
							if (typeof io < "u") return io
							throw new Error(
								"Socket.io client not found. Should be globally available or passed via options.client",
							)
						},
					},
					{
						key: "listen",
						value: function (s, f, d) {
							return this.channel(s).listen(f, d)
						},
					},
					{
						key: "channel",
						value: function (s) {
							return (
								this.channels[s] ||
									(this.channels[s] = new ye(this.socket, s, this.options)),
								this.channels[s]
							)
						},
					},
					{
						key: "privateChannel",
						value: function (s) {
							return (
								this.channels["private-" + s] ||
									(this.channels["private-" + s] = new ge(
										this.socket,
										"private-" + s,
										this.options,
									)),
								this.channels["private-" + s]
							)
						},
					},
					{
						key: "presenceChannel",
						value: function (s) {
							return (
								this.channels["presence-" + s] ||
									(this.channels["presence-" + s] = new Di(
										this.socket,
										"presence-" + s,
										this.options,
									)),
								this.channels["presence-" + s]
							)
						},
					},
					{
						key: "leave",
						value: function (s) {
							var d = [s, "private-" + s, "presence-" + s]
							d.forEach((N) => {
								this.leaveChannel(N)
							})
						},
					},
					{
						key: "leaveChannel",
						value: function (s) {
							this.channels[s] &&
								(this.channels[s].unsubscribe(), delete this.channels[s])
						},
					},
					{
						key: "socketId",
						value: function () {
							return this.socket.id
						},
					},
					{
						key: "disconnect",
						value: function () {
							this.socket.disconnect()
						},
					},
				]),
				a
			)
		})(Rt),
		zi = ((l) => {
			D(a, l)
			var h = H(a)
			function a() {
				var c
				return L(this, a), (c = h.apply(this, arguments)), (c.channels = {}), c
			}
			return (
				R(a, [
					{ key: "connect", value: () => {} },
					{ key: "listen", value: (s, f, d) => new dt() },
					{ key: "channel", value: (s) => new dt() },
					{ key: "privateChannel", value: (s) => new _e() },
					{ key: "encryptedPrivateChannel", value: (s) => new Hi() },
					{ key: "presenceChannel", value: (s) => new Mi() },
					{ key: "leave", value: (s) => {} },
					{ key: "leaveChannel", value: (s) => {} },
					{ key: "socketId", value: () => "fake-socket-id" },
					{ key: "disconnect", value: () => {} },
				]),
				a
			)
		})(Rt),
		be = (() => {
			function l(h) {
				L(this, l),
					(this.options = h),
					this.connect(),
					this.options.withoutInterceptors || this.registerInterceptors()
			}
			return (
				R(l, [
					{
						key: "channel",
						value: function (a) {
							return this.connector.channel(a)
						},
					},
					{
						key: "connect",
						value: function () {
							if (this.options.broadcaster == "reverb")
								this.connector = new fe(
									at(at({}, this.options), { cluster: "" }),
								)
							else if (this.options.broadcaster == "pusher")
								this.connector = new fe(this.options)
							else if (this.options.broadcaster == "socket.io")
								this.connector = new pe(this.options)
							else if (this.options.broadcaster == "null")
								this.connector = new zi(this.options)
							else if (
								typeof this.options.broadcaster == "function" &&
								Ni(this.options.broadcaster)
							)
								this.connector = new this.options.broadcaster(this.options)
							else
								throw new Error(
									"Broadcaster "
										.concat(st(this.options.broadcaster), " ")
										.concat(this.options.broadcaster, " is not supported."),
								)
						},
					},
					{
						key: "disconnect",
						value: function () {
							this.connector.disconnect()
						},
					},
					{
						key: "join",
						value: function (a) {
							return this.connector.presenceChannel(a)
						},
					},
					{
						key: "leave",
						value: function (a) {
							this.connector.leave(a)
						},
					},
					{
						key: "leaveChannel",
						value: function (a) {
							this.connector.leaveChannel(a)
						},
					},
					{
						key: "leaveAllChannels",
						value: function () {
							for (var a in this.connector.channels) this.leaveChannel(a)
						},
					},
					{
						key: "listen",
						value: function (a, c, s) {
							return this.connector.listen(a, c, s)
						},
					},
					{
						key: "private",
						value: function (a) {
							return this.connector.privateChannel(a)
						},
					},
					{
						key: "encryptedPrivate",
						value: function (a) {
							if (this.connector instanceof pe)
								throw new Error(
									"Broadcaster "
										.concat(st(this.options.broadcaster), " ")
										.concat(
											this.options.broadcaster,
											" does not support encrypted private channels.",
										),
								)
							return this.connector.encryptedPrivateChannel(a)
						},
					},
					{
						key: "socketId",
						value: function () {
							return this.connector.socketId()
						},
					},
					{
						key: "registerInterceptors",
						value: function () {
							typeof Vue == "function" &&
								Vue.http &&
								this.registerVueRequestInterceptor(),
								typeof axios == "function" &&
									this.registerAxiosRequestInterceptor(),
								typeof jQuery == "function" && this.registerjQueryAjaxSetup(),
								(typeof Turbo > "u" ? "undefined" : st(Turbo)) === "object" &&
									this.registerTurboRequestInterceptor()
						},
					},
					{
						key: "registerVueRequestInterceptor",
						value: function () {
							Vue.http.interceptors.push((c, s) => {
								this.socketId() &&
									c.headers.set("X-Socket-ID", this.socketId()),
									s()
							})
						},
					},
					{
						key: "registerAxiosRequestInterceptor",
						value: function () {
							axios.interceptors.request.use(
								(c) => (
									this.socketId() &&
										(c.headers["X-Socket-Id"] = this.socketId()),
									c
								),
							)
						},
					},
					{
						key: "registerjQueryAjaxSetup",
						value: function () {
							typeof jQuery.ajax < "u" &&
								jQuery.ajaxPrefilter((c, s, f) => {
									this.socketId() &&
										f.setRequestHeader("X-Socket-Id", this.socketId())
								})
						},
					},
					{
						key: "registerTurboRequestInterceptor",
						value: function () {
							document.addEventListener("turbo:before-fetch-request", (c) => {
								c.detail.fetchOptions.headers["X-Socket-Id"] = this.socketId()
							})
						},
					},
				]),
				l
			)
		})()
	var we = Li(me(), 1)
	window.EchoFactory = be
	window.Pusher = we.default
})()
/*! Bundled license information:

pusher-js/dist/web/pusher.js:
  (*!
   * Pusher JavaScript Library v7.6.0
   * https://pusher.com/
   *
   * Copyright 2020, Pusher
   * Released under the MIT licence.
   *)
*/
