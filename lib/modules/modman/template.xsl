<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:template match="modules">
    <h2>
      <xsl:value-of select="@title" />
    </h2>
    <div class="modman">
      <xsl:apply-templates select="." mode="module-extras" />

      <form method="post" action="?q=modman.rpc&amp;action=addremove&amp;destination={/page/@urlEncoded}">
        <table>
          <tbody>
            <xsl:apply-templates select="module" />
          </tbody>
        </table>

        <xsl:if test="@mode = 'addremove'">
          <p class="hint">Если при установке модулей возникают проблемы, вы можете скачать и установить их вручную, воспользовавшись специальной ссылкой.</p>
          <input class="form-submit" type="submit" value="Применить" />
        </xsl:if>
      </form>
    </div>
  </xsl:template>

  <xsl:template match="module">
    <tr>
      <xsl:attribute name="class">
        <xsl:if test="@installed">
          <xsl:text> installed</xsl:text>
        </xsl:if>
        <xsl:if test="not(@installed)">
          <xsl:text> uninstalled</xsl:text>
        </xsl:if>
        <xsl:text> section-</xsl:text>
        <xsl:value-of select="@section" />
      </xsl:attribute>

      <xsl:if test="../@mode = 'addremove'">
        <td>
          <input type="checkbox" name="modules[]" value="{@id}" id="check-{@id}">
            <xsl:if test="@installed">
              <xsl:attribute name="checked">
                <xsl:text>checked</xsl:text>
              </xsl:attribute>
            </xsl:if>
          </input>
        </td>
      </xsl:if>

      <xsl:if test="../@mode = 'settings'">
        <td>
          <a href="?q=admin&amp;cgroup=system&amp;module=modman&amp;mode=config&amp;name={@id}&amp;destination={/page/@urlEncoded}">
            <img src="lib/modules/modman/configure.png" alt="settings" />
          </a>
        </td>
      </xsl:if>

      <td>
        <label for="check-{@id}">
          <xsl:value-of select="@id" />
        </label>
        <span class="description">
          <xsl:value-of select="@name" />
        </span>
      </td>

      <td>
        <xsl:if test="@docurl">
          <a href="{@docurl}" class="icon docurl">
            <span>
              <xsl:text>документация</xsl:text>
            </span>
          </a>
        </xsl:if>
      </td>

      <td>
        <xsl:if test="@url">
          <a href="{@url}" class="icon download">
            <span>
              <xsl:value-of select="@filename" />
            </span>
          </a>
        </xsl:if>
      </td>

      <!-- Номер устанвленной версии, если модуль не установлен — номер доступной. -->
      <td class="version">
        <xsl:text>v</xsl:text>
        <xsl:if test="@installed">
          <xsl:value-of select="@version.local" />
        </xsl:if>
        <xsl:if test="not(@installed)">
          <xsl:value-of select="@version" />
        </xsl:if>
      </td>
    </tr>
  </xsl:template>

  <!-- Дополнительные элементы для фильтрации модулей. -->
  <xsl:template match="modules[@mode = 'addremove']" mode="module-extras">
    <label class="filter">
      <span>Показать:</span>
      <select>
        <option value="">все модули</option>
        <option value="installed">только установленные</option>
        <option value="uninstalled">не установленные</option>
        <option value="section-base">Основная функциональность</option>
        <option value="section-admin">Администрирование</option>
        <option value="section-core">Ядро</option>
        <option value="section-blog">Блоги</option>
        <option value="section-spam">Спам</option>
        <option value="section-commerce">Коммерция</option>
        <option value="section-interaction">Интерактив</option>
        <option value="section-performance">Производительность</option>
        <option value="section-service">Служебные</option>
        <option value="section-multimedia">Мультимедиа</option>
        <option value="section-syndication">Обмен данными</option>
        <option value="section-templating">Шаблоны</option>
        <xsl:if test="count(module[@section = 'visual'])">
          <option value="section-visual">Визуальные редакторы</option>
        </xsl:if>
        <xsl:if test="count(module[@section = 'custom'])">
          <option value="section-custom">Локальные</option>
        </xsl:if>
      </select>
    </label>
  </xsl:template>

  <xsl:include href="../admin/template.xsl" />
</xsl:stylesheet>
