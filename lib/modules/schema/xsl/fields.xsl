<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="../../admin/template.xsl" />

  <!-- список полей в типе документа -->
  <xsl:template match="content[@name='typefields']" mode="content">
    <h2>
      <xsl:value-of select="@title" />
    </h2>
    <xsl:apply-templates select="node/fields" mode="typefields" />
    <xsl:apply-templates select="types" />
  </xsl:template>

  <xsl:template match="types">
    <h3>Добавить поле</h3>
    <form action="{../@base}/add" method="get">
      <input type="hidden" name="type" value="{../node/@name}" />
      <select name="class">
        <xsl:for-each select="type">
          <option value="{@name}">
            <xsl:value-of select="@title" />
          </option>
        </xsl:for-each>
      </select>
      <input type="submit" value="Продолжить" /> или <a href="admin/structure/types">вернуться к списку типов</a>
      <input type="hidden" name="destination" value="{/page/@back}" />
    </form>
  </xsl:template>

  <xsl:template match="fields" mode="typefields">
    <xsl:variable name="groups" select="not(not(field[@group]))" />
    <xsl:variable name="flags" select="not(not(field[@indexed]))" />
    <table class="classic">
      <thead>
        <tr>
          <th/>
          <th>Заголовок</th>
          <th>Имя</th>
          <th>Тип</th>
          <th>Вес</th>
          <xsl:if test="$groups">
            <th>Вкладка</th>
          </xsl:if>
          <xsl:if test="$flags">
            <th/>
          </xsl:if>
        </tr>
      </thead>
      <tbody>
        <xsl:for-each select="field">
          <xsl:sort select="@weight" data-type="number" />
          <xsl:sort select="@label" data-type="text" />
          <tr>
            <xsl:attribute name="class">
              <xsl:if test="not(@required)">
                <xsl:text>optional</xsl:text>
              </xsl:if>
            </xsl:attribute>
            <td>
              <xsl:value-of select="position()" />
              <xsl:text>.</xsl:text>
            </td>
            <td class="label">
              <a href="{../../../@base}/edit?type={../../@name}&amp;field={@name}&amp;destination={/page/@back}">
                <xsl:value-of select="@label" />
                <xsl:if test="not(@label)">
                  <em>Скрытое поле</em>
                </xsl:if>
              </a>
              <xsl:if test="@required">*</xsl:if>
            </td>
            <td>
              <xsl:value-of select="@name" />
            </td>
            <td>
              <xsl:value-of select="@typeName" />
              <xsl:if test="@docurl">
                <a href="{@docurl}">
                  <img src="lib/modules/admin/styles/admin/images/help.gif" alt="edit" />
                </a>
              </xsl:if>
            </td>
            <td>
              <xsl:value-of select="@weight" />
            </td>
            <xsl:if test="$groups">
              <td>
                <xsl:value-of select="@group" />
              </td>
            </xsl:if>
            <xsl:if test="$flags">
              <td class="mono">
                <!--
                <xsl:if test="@required">
                  <span title="Обязательное">R</span>
                </xsl:if>
                -->
                <xsl:if test="@indexed">
                  <span title="Поиск, сортировка">I</span>
                </xsl:if>
              </td>
            </xsl:if>
          </tr>
        </xsl:for-each>
      </tbody>
    </table>
  </xsl:template>
</xsl:stylesheet>
